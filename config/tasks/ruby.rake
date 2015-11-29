namespace :rvm do
  task :update_rvm_key do
    on roles(:all) do
      execute :gpg, "--keyserver hkp://keys.gnupg.net --recv-keys D39DC0E3"
    end
  end
end
