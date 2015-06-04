
INSERT INTO `weaving_user` (`usr_id`, `usr_twitter_id`, `usr_twitter_username`, `grp_id`, `usr_avatar`, `usr_first_name`, `usr_full_name`, `usr_last_name`, `usr_middle_name`, `usr_phone`, `usr_status`, `usr_user_name`, `usr_username_canonical`, `usr_email`, `usr_email_canonical`, `usr_password`, `usr_password_requested_at`, `usr_salt`, `usr_locked`, `usr_credentials_expired`, `usr_credentials_expires_at`, `usr_confirmation_token`, `usr_expired`, `usr_expires_at`, `usr_last_login`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Gordon', 'gordon', 'thyssy@gmail.com', 'thyssy@gmail.com', 'n+HA1MGPxzGVU9LOtvih9DDJ9Lh5hflMMzH3iD7CkDBUqkS9KYlM9AQhlQ1gSW1wcFlTuM1QY7xTXBEvCLpljg==', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL);


--
-- Constraints for table `weaving_user_role`
--
ALTER TABLE `weaving_user_role`
  ADD CONSTRAINT `FK_95AD963FD60322AC` FOREIGN KEY (`role_id`) REFERENCES `weaving_role` (`id`),
  ADD CONSTRAINT `FK_95AD963FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `weaving_user` (`usr_id`);

INSERT INTO `weaving_role` (`id`, `name`, `role`) VALUES
(1, 'Weaver', 'ROLE_USER'),
(2, 'Super Weaver', 'ROLE_ADMIN'),
(3, 'Über Weaver', 'ROLE_SUPER_ADMIN');
