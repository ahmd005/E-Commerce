<?php

namespace App\Http\Requests\AuthValidation;

trait AuthenticationValidation
{
    protected function getPrimaryRules(bool $unique): array
    {
        if ($unique) {
            return [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ];
        }

        return [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ];
    }

    protected function getSecondaryRules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'password_confirmation' => 'required|same:password',
        ];
    }

    protected function getPrimaryMessages(): array
    {
        return [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'كلمة المرور يجب أن تكون نصاً.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }

    protected function getSecondaryMessages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'name.string' => 'الاسم يجب أن يكون نصاً.',
            'name.min' => 'الاسم يجب أن يحتوي على 3 أحرف على الأقل.',
            'name.max' => 'الاسم لا يمكن أن يتجاوز 255 حرفاً.',
            'password_confirmation.required' => 'تأكيد كلمة المرور مطلوب.',
            'password_confirmation.same' => 'تأكيد كلمة المرور يجب أن يطابق كلمة المرور.',
        ];
    }

    protected function getValidationMessages(): array
    {
        return array_merge(
            $this->getPrimaryMessages(),
            $this->getSecondaryMessages()
        );
    }
}
