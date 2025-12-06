<?php

namespace App\DataFixtures;

use App\Entity\Appointment;
use App\Entity\Availability;
use App\Entity\Doctor;
use App\Entity\MedicalService;
use App\Entity\Specialty;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Create Specialties
        $specialties = [];
        $specialtyNames = ['General Practitioner', 'Dentist', 'Dermatologist', 'Cardiologist', 'Pediatrician'];
        
        foreach ($specialtyNames as $name) {
            $specialty = new Specialty();
            $specialty->setName($name);
            $specialty->setDescription("Specialist in $name");
            $manager->persist($specialty);
            $specialties[] = $specialty;
        }

        // 2. Create Doctors (Users + Doctor Profiles)
        $doctors = [];
        for ($i = 1; $i <= 5; $i++) {
            // Create User
            $user = new User();
            $user->setEmail("doctor$i@medibook.com");
            $user->setRoles(['ROLE_DOCTOR']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);

            // Create Doctor Profile
            $doctor = new Doctor();
            $doctor->setFirstName("Dr. First$i");
            $doctor->setLastName("Last$i");
            $doctor->setAddress("$i Medical Street");
            $doctor->setCity("Paris");
            $doctor->setZipCode("7500$i");
            $doctor->setUser($user);
            $doctor->setSpecialty($specialties[$i % count($specialties)]);
            $manager->persist($doctor);
            $doctors[] = $doctor;

            // Create Services
            $service = new MedicalService();
            $service->setName("Consultation");
            $service->setPrice(50.0 + ($i * 10));
            $service->setDuration(30);
            $doctor->addMedicalService($service);
            $manager->persist($service);

            // Create Availability
            $availability = new Availability();
            $availability->setDayOfWeek(1); // Monday
            $availability->setStartTime(new \DateTime('09:00'));
            $availability->setEndTime(new \DateTime('17:00'));
            $doctor->addAvailability($availability);
            $manager->persist($availability);
        }

        // 3. Create Patients (Users)
        $patients = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->setEmail("patient$i@medibook.com");
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $patients[] = $user;
        }

        // 4. Create Appointments
        foreach ($doctors as $index => $doctor) {
            // Past appointment
            $appointment = new Appointment();
            $appointment->setDoctor($doctor);
            $appointment->setPatient($patients[$index]); // Assign first few patients
            $appointment->setService($doctor->getMedicalServices()->first());
            $appointment->setStartAt(new \DateTimeImmutable('-1 day 10:00'));
            $appointment->setEndAt(new \DateTimeImmutable('-1 day 10:30'));
            $appointment->setStatus('COMPLETED');
            $manager->persist($appointment);

            // Future appointment
            $appointment = new Appointment();
            $appointment->setDoctor($doctor);
            $appointment->setPatient($patients[$index + 1] ?? $patients[0]);
            $appointment->setService($doctor->getMedicalServices()->first());
            $appointment->setStartAt(new \DateTimeImmutable('+1 day 14:00'));
            $appointment->setEndAt(new \DateTimeImmutable('+1 day 14:30'));
            $appointment->setStatus('SCHEDULED');
            $manager->persist($appointment);
        }

        $manager->flush();
    }
}
