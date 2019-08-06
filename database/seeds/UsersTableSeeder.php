<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Models\User;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminUser = new User();
        $adminUser->name = 'Admin';
        $adminUser->email = 'admin@admin.com';
        $adminUser->password = Hash::make('admin');
        $adminUser->isActive = (int) true;
        $adminUser->confirmationToken = md5(uniqid($adminUser->email, true) . time());

        $this->em->persist($adminUser);
        $this->em->flush();
//        $registerController = new RegisterController();
//        $registerController->attachRoles($adminUser);
    }
}
