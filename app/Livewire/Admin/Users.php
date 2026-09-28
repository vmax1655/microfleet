<?php

namespace App\Livewire\Admin;

use App\Support\MockData;
use App\Support\Nav;
use App\Support\Rbac;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Users & Roles')]
class Users extends Component
{
    public function render()
    {
        return view('livewire.admin.users', [
            'users' => MockData::systemUsers(),
            'roles' => MockData::roles(),
            'modules' => Nav::modules(),
            'matrix' => Rbac::matrix(),
            'permissions' => Rbac::PERMISSIONS,
            'branches' => MockData::branches(),
        ]);
    }
}
