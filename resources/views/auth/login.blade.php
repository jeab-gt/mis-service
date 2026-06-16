<x-guest-layout>
    <div class="p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-1">
            {{ app()->getLocale() === 'th' ? 'เข้าสู่ระบบ' : 'Sign In' }}
        </h2>
        <p class="text-sm text-gray-500 mb-6">
            {{ app()->getLocale() === 'th' ? 'กรุณาระบุรหัสพนักงานและรหัสผ่าน' : 'Enter your employee code and password' }}
        </p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="employee_code" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ app()->getLocale() === 'th' ? 'รหัสพนักงาน' : 'Employee Code' }}
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ti ti-id-badge-2 text-gray-400"></i>
                    </div>
                    <input id="employee_code" type="text" name="employee_code"
                           value="{{ old('employee_code') }}" required autofocus autocomplete="username"
                           placeholder="EMP001"
                           class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('employee_code') border-red-500 @enderror">
                </div>
                @error('employee_code')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ app()->getLocale() === 'th' ? 'รหัสผ่าน' : 'Password' }}
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ti ti-lock text-gray-400"></i>
                    </div>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('password') border-red-500 @enderror">
                </div>
                @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input id="remember_me" type="checkbox" name="remember" class="rounded text-indigo-600">
                    <span class="text-sm text-gray-600">{{ app()->getLocale() === 'th' ? 'จดจำฉัน' : 'Remember me' }}</span>
                </label>
            </div>

            <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition flex items-center justify-center space-x-2">
                <i class="ti ti-login"></i>
                <span>{{ app()->getLocale() === 'th' ? 'เข้าสู่ระบบ' : 'Sign In' }}</span>
            </button>
        </form>

        <div class="mt-6 p-3 bg-gray-50 rounded-xl text-xs text-gray-500 space-y-1">
            <p class="font-semibold text-gray-600">{{ app()->getLocale() === 'th' ? 'บัญชีทดสอบ:' : 'Test Accounts:' }}</p>
            <p>EMP001 / password (super_admin — Head Office)</p>
            <p>EMP002 / password (it_manager — Head Office, parent factory)</p>
            <p>EMP004 / password (it_manager — Factory 1)</p>
            <p>EMP006 / password (requester — Factory 1)</p>
            <p>EMP008 / password (requester — Factory 2)</p>
        </div>
    </div>
</x-guest-layout>
