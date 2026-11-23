ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `birthday` DATE NULL AFTER `avatar_url`;
