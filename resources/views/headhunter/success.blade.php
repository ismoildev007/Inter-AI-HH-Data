<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HeadHunter Connected</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-2xl rounded-2xl p-8 max-w-md w-full text-center">
        <div class="flex justify-center mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2l4 -4m-7 7a9 9 0 110-18a9 9 0 010 18z" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-green-700 mb-2">Connected Successfully!</h1>
        <p class="text-gray-600 mb-6">
            Your HeadHunter account has been connected successfully. You can now sync your vacancies and candidate data.
        </p>

        <a href="https://vacancies.inter-ai.uz/"
           target="_blank"
           class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg shadow hover:bg-green-700 transition">
            Go to Dashboard
        </a>
    </div>

    <script>
        // Auto-redirect after 4 seconds
        setTimeout(() => {
            window.location.href = 'https://vacancies.inter-ai.uz/';
        }, 4000);
    </script>
</body>
</html>
