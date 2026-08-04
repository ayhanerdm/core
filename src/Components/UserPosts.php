<?php
namespace ayhanerdm\Core\Components;

class UserPosts {
    public static function UserPost(object $userProfile, object $userPost): string {
        $html = '<div class="user-post" id="user-post-' . $userPost->id . '">
            <div class="user-post__header">
                <div class="user-post__header-left">
                    <a href="' . $userProfile->profile_url . '">
                        <img src="' . $userProfile->avatar_url . '" alt="' . $userProfile->safe_display_name->public . ' Avatar Image" id="profileAvatar" class="user-post__avatar">
                    </a>
                    <div class="user-post__header-left__info">
                        <h3 class="user-display_name">
                            <a href="' . $userProfile->profile_url . '">
                                ' . $userProfile->display_name . '
                            </a>
                        </h3>
                        <p class="user-post-date">
                            <a href="' . $userProfile->profile_url . '">
                                @' . $userProfile->username . '
                            </a>
                            <a href="' . $userProfile->profile_url . '/posts/' . $userPost->id . '">
                                ' . $userPost->posted_at . '
                            </a>
                        </p>
                    </div>
                </div>
                <div class="user-post__header-right">
                    <button data-dialog-target="' . $userPost->id . '" class="user-post__options-toggle" aria-label="Post Options">
                        <i class="fa-solid fa-caret-down"></i>
                    </button>

                    <dialog class="user-post__options" id="' . $userPost->id . '">
                        <a href="https://api.ayhanerdm.dynu.net/post/delete/' . $userPost->id . '" class="user-post__delete">
                            <i class="fa-solid fa-trash"></i> Sil
                        </a>
                    </dialog>
                </div>
            </div>
            <div class="user-post__body">';
            foreach(explode("\n", $userPost->post_content) as $line) {
                $html .= '<p>' . htmlspecialchars($line) . '</p>';
            }
            $html .= '</div>
        </div>';

        return $html;
    }
}