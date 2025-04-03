<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Token;

class ResetPasswordEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    /**
     * Khởi tạo instance mới của mail.
     *
     * @param User $user
     * @param Token $token
     * @return void
     */
    public function __construct(User $user, Token $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Build email message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.reset-password')
                    ->subject('Mã đặt lại mật khẩu')
                    ->with([
                        'user' => $this->user,
                        'token' => $this->token,
                        'expiresAt' => $this->token->expires_at
                    ]);
    }
}