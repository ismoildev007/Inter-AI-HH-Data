<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HeadHunter Connected</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 flex items-center justify-center min-h-screen font-sans">
    <div class="bg-white shadow-2xl rounded-2xl p-8 max-w-md w-full text-center relative overflow-hidden">
        <!-- Decorative top bar -->
        <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-blue-600 to-indigo-800 rounded-t-2xl"></div>

        <!-- HeadHunter Logo -->
        <div class="flex justify-center mb-6 mt-4">
            <div class="flex items-center space-x-2">
                <div class="w-14 h-14 rounded-full bg-blue-600 flex items-center justify-center shadow-lg">
                    <span class="text-white font-bold text-2xl">hh</span>
                </div>
                <span class="text-2xl font-bold text-gray-700">HeadHunter</span>
            </div>
        </div>

        <!-- Checkmark -->
        <div class="flex justify-center mb-6 animate-bounce">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2l4 -4m7 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-extrabold text-blue-700 mb-2">Connected Successfully!</h1>
        <p class="text-gray-600 mb-6 leading-relaxed">
            Your <span class="font-semibold text-blue-700">HeadHunter</span> account has been connected.
            You can now synchronize your vacancies and manage applicants directly from your dashboard.
        </p>

        <!-- Button -->
        <a href="https://vacancies.inter-ai.uz/"
           target="_blank"
           class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg hover:bg-blue-700 hover:shadow-xl transition transform hover:scale-105">
            Go to Dashboard
        </a>

        <!-- Auto redirect notice -->
        <p class="text-sm text-gray-400 mt-4">Redirecting you automatically in a few seconds...</p>

        <!-- Bottom decorative line -->
        <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-indigo-800 to-blue-600 rounded-b-2xl"></div>
    </div>

    <script>
        // Smooth fade-in
        document.body.style.opacity = 0;
        document.addEventListener('DOMContentLoaded', () => {
            document.body.style.transition = 'opacity 0.8s';
            document.body.style.opacity = 1;
        });

        // Auto-redirect after 4 seconds
        setTimeout(() => {
            window.location.href = 'https://vacancies.inter-ai.uz/';
        }, 4000);
    </script>
</body>
</html>
