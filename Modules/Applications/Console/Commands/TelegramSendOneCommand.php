<?php
//
//namespace Modules\Applications\Console\Commands;
//
//use App\Models\Application;
//use App\Models\HhAccount;
//use App\Models\User;
//use App\Models\Vacancy;
//use Illuminate\Console\Command;
//use Illuminate\Support\Arr;
//use Illuminate\Support\Facades\Log;
//use Modules\Users\Repositories\HhAccountRepositoryInterface;
//use Modules\Vacancies\Interfaces\HHVacancyInterface;
//use Telegram\Bot\Api;
//use Telegram\Bot\Keyboard\Keyboard;
//use Telegram\Bot\Laravel\Facades\Telegram;
//
//class TelegramSendOneCommand extends Command
//{
//    protected $signature = 'announcement:career-tracking';
//    protected $description = 'Send AI Career Tracking announcement to all users';
//
//    public function handle()
//    {
//        $this->info('Starting Career Tracking announcement...');
//
//        $users = User::whereNotNull('chat_id')->get();
//
//        foreach ($users as $user) {
//            $this->info("Sending to user: {$user->id}");
//
//            try {
//                $lang = $user->language ?? 'uz';
//                $messageText = $this->buildMessage($lang);
//
//                $inlineKeyboard = Keyboard::make()
//                    ->inline()
//                    ->row([
//                        Keyboard::inlineButton([
//                            'text' => '📝 Ro\'yxatdan o\'tish',
//                            'url'  => 'https://forms.gle/Pkv2EGtTWoK9zUUZA',
//                        ]),
//                    ]);
//
//                Telegram::bot('mybot')->sendMessage([
//                    'chat_id'      => $user->chat_id,
//                    'text'         => $messageText,
//                    'parse_mode'   => 'Markdown',
//                    'reply_markup' => $inlineKeyboard,
//                ]);
//
//                $this->info("✅ Message sent to user {$user->id}");
//            } catch (\Exception $e) {
//                Log::error("❌ Failed to send announcement to user {$user->id}: " . $e->getMessage());
//                $this->error("Failed to send message to user {$user->id}: {$e->getMessage()}");
//            }
//        }
//
//        $this->info('Career Tracking announcement completed!');
//    }
//
//    private function buildMessage(string $lang): string
//    {
//        if ($lang === 'uz') {
//            return "*Karyerangizni qachon nazorat qilasiz?*\n\n" .
//                "Endi Inter-AI yangi bosqichda — biz ish topishdan tashqari, sizning karyerangizni real vaqtda tahlil qilib, o'sishingizni kuzatadigan \"AI Career Tracking\" tizimini yo'lga qo'yyapmiz.\n\n" .
//                "Bu xizmat hozircha faqat oldindan ro'yxatdan o'tgan foydalanuvchilar uchun taqdim etiladi.\n\n" .
//                "🎯 *Siz uchun tizim:*\n" .
//                "- Karyera darajangizni aniqlaydi\n" .
//                "- Karyerangizni nazorat qilib bo'radi\n" .
//                "- O'sish nuqtalarini taklif qiladi\n" .
//                "- Oyma-oy AI hisobot yuboradi\n" .
//                "- Kuchli va zaif tomonlaringizni tahlil qiladi\n" .
//                "- Sizga mos lavozim va o'sish yo'nalishini tavsiya qiladi\n\n" .
//                "⚠️ Ro'yxatdan o'tgan foydalanuvchilarning faqat *100 tasini* qabul qilamiz\n\n" .
//                "👇 Ro'yxatdan o'tish uchun pastdagi tugmani bosing";
//        }
//
//        if ($lang === 'ru') {
//            return "*Когда вы будете контролировать свою карьеру?*\n\n" .
//                "Теперь Inter-AI на новом уровне — помимо поиска работы, мы запускаем систему \"AI Career Tracking\", которая анализирует вашу карьеру в реальном времени и отслеживает ваш рост.\n\n" .
//                "Эта услуга пока доступна только для пользователей, зарегистрировавшихся заранее.\n\n" .
//                "🎯 *Система для вас:*\n" .
//                "- Определяет уровень вашей карьеры\n" .
//                "- Контролирует вашу карьеру\n" .
//                "- Предлагает точки роста\n" .
//                "- Отправляет ежемесячный AI-отчет\n" .
//                "- Анализирует ваши сильные и слабые стороны\n" .
//                "- Рекомендует подходящие должности и направления роста\n\n" .
//                "⚠️ Мы принимаем только *100* зарегистрированных пользователей\n\n" .
//                "👇 Нажмите кнопку ниже для регистрации";
//        }
//
//        return "*When will you take control of your career?*\n\n" .
//            "Inter-AI is now at a new level — in addition to job search, we are launching an \"AI Career Tracking\" system that analyzes your career in real-time and tracks your growth.\n\n" .
//            "This service is currently available only for pre-registered users.\n\n" .
//            "🎯 *The system for you:*\n" .
//            "- Determines your career level\n" .
//            "- Controls your career\n" .
//            "- Suggests growth points\n" .
//            "- Sends monthly AI reports\n" .
//            "- Analyzes your strengths and weaknesses\n" .
//            "- Recommends suitable positions and growth directions\n\n" .
//            "⚠️ We accept only *100* registered users\n\n" .
//            "👇 Click the button below to register";
//    }
//}
