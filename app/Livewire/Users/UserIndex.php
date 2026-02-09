<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class UserIndex extends Component
{
    use AuthorizesRequests;

    public $users;

    public function mount()
    {
        if(auth()->user()->hasRole('Super Admin')) {
            $this->users = User::all();
        } else {
            $this->users = User::where('company_id', auth()->user()->company_id)->get();
        }
    }

    #[On('delete')]
    public function delete($id)
    {
        $this->authorize('user-delete');

        $user = User::findOrFail($id);
        $user->delete();

        return to_route('users')->with('success', 'User deleted successfully.');
    }
    public function render()
    {
        return view('livewire.users.user-index');
    }
}
