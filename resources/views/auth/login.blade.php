@extends('layouts.app')
@section('title', 'تسجيل الدخول')

@section('body')
<div class="min-h-screen relative flex items-center justify-center p-4 overflow-hidden bg-gradient-to-br from-cyan-50 via-white to-emerald-50 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950/30">
    <div class="absolute top-[-10%] left-[-10%] w-[400px] h-[400px] rounded-full bg-cyan-300/30 dark:bg-cyan-500/10 blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[500px] h-[500px] rounded-full bg-emerald-300/30 dark:bg-emerald-500/10 blur-3xl pointer-events-none"></div>

    <div class="relative w-full max-w-md">
        <div class="text-center mb-7">
            <div class="relative inline-flex items-center justify-center mb-4">
                <div class="absolute inset-0 rounded-2xl blur-xl opacity-60" style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);"></div>
                <div class="relative w-20 h-20 rounded-2xl flex items-center justify-center shadow-lg text-white" style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-800 dark:text-gray-100 tracking-tight">تعبئة المياه</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">إدارة استهلاك المياه للعائلات</p>
        </div>

        <div class="rounded-xl py-6 shadow-sm border-0 shadow-soft bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl px-6">
            <h2 class="text-center text-lg font-semibold mb-4 dark:text-gray-100">تسجيل الدخول</h2>

            @if ($errors->any())
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl p-3 text-sm text-red-700 dark:text-red-400 text-center mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/login" class="space-y-4">
                @csrf
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium dark:text-gray-300">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email"
                           placeholder="example@email.com" required autofocus
                           dir="ltr"
                           value="{{ old('email') }}"
                           class="w-full h-9 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 px-3 py-1 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </div>
                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium dark:text-gray-300">كلمة المرور</label>
                    <input type="password" id="password" name="password"
                           placeholder="أدخل كلمة المرور" required
                           dir="ltr"
                           class="w-full h-9 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 px-3 py-1 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </div>
                <button type="submit"
                        class="w-full h-12 rounded-md text-white font-semibold shadow-lg border-0"
                        style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);">
                    تسجيل الدخول
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="/register" class="text-sm text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 font-medium">
                    ليس لديك حساب؟ إنشاء حساب جديد
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">
            بياناتك محفوظة بأمان ومتاحة من أي جهاز
        </p>
    </div>
</div>
@endsection
