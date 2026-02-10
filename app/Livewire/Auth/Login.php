<?php

namespace App\Livewire\Auth;

use Livewire\Component;

class Login extends Component
{
    public $username;
    public $password;

    public $rememberMe = false;

    public function mount()
    {
        if (authUser()) {
            return to_route('dashboard');
        }
    }
    public function rules()
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function login()
    {
        $this->validate();

        if (auth()->attempt(['username' => $this->username, 'password' => $this->password], $this->rememberMe)) {
            return redirect()->route('dashboard');
        } else {
            return $this->addError('username', trans('auth.failed'));
        }
    }
    public function render()
    {
        return view('livewire.auth.login');
    }
}
