<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\RoleModel;

class Auth extends BaseController
{
    public function register()
    {
        helper('form');

        // Only Allow Owner to Register New Users
        $roleModel = new RoleModel();
        $userModel = new UserModel();
        $currentUser = $userModel->getUserWithRole(session()->get('user_id'));
        if ($currentUser['role_name'] !== 'Owner') {
            return redirect()->to('/')->with('error', 'You are not authorized to access this page.');
        }
        
        $data = [
            'roles' => $roleModel->findAll(),
            'currentUser' => $currentUser,
            'users' => $userModel->getUsersfirstNameLastNameEmailRole()
        ];
        

        if($this->request->getMethod() === 'POST') {
            $validationRules = [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|valid_email|is_unique[users.email]',
                'password' => 'required|min_length[6]',
                'role_id' => 'required|integer'
            ];

            if (!$this->validate($validationRules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $userModel = new UserModel();
            $userModel->save([
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'email' => $this->request->getPost('email'),
                'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                'role_id' => $this->request->getPost('role_id')
            ]);

            return redirect()->to('/register')->with('success', 'User registered successfully.');
        }

        return view('Reusables/menu') . view('auth/register', $data);
    }

    public function login()
    {
        helper('form');

        if($this->request->getMethod() === 'POST') {
            $validationRules = [
                'email' => 'required|valid_email',
                'password' => 'required'
            ];

            if (!$this->validate($validationRules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $userModel = new UserModel();
            $user = $userModel->where('email', $this->request->getPost('email'))->first();
            
            if ($user && password_verify($this->request->getPost('password'), $user['password_hash'])) {
                session()->set([
                    'user_id' => $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role' => $userModel->getRoleNameByRoleId($user['role_id']),
                    'logged_in' => true
                ]);
                return redirect()->to('/dashboard')->with('success', 'Logged in successfully.');
            } else {
                return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
            }
        }

        return view('auth/login');
    }

    public function dashboard()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Please log in to access the dashboard.');
        }

        $userModel = new UserModel();
        $roleModel = new RoleModel();

        // $data = [
            
        // ];

        return view('Reusables/menu') . view('auth/dashboard');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Logged out successfully.');
    }
}
