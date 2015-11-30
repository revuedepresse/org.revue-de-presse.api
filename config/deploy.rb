lock '3.4'

set :default_env, {
  'PATH' => '~/.nvm/versions/node/v4.2.2/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
  'SYMFONY_ENV' => 'prod',
  'SYMFONY__AES__KEY' => ENV['SYMFONY__AES__KEY'],
  'SYMFONY__AES__IV' => ENV['SYMFONY__AES__IV'],
  'SYMFONY__ANALYTICS__HOST' => ENV['SYMFONY__ANALYTICS__HOST'],
  'SYMFONY__ANALYTICS__SITE_ID' => ENV['SYMFONY__ANALYTICS__SITE_ID'],
  'SYMFONY__ASSETIC__NODE' => ENV['SYMFONY__ASSETIC__NODE'],
  'SYMFONY__ASSETIC__MODULES' => ENV['SYMFONY__ASSETIC__MODULES'],
  'SYMFONY__APC__HOST' => ENV['SYMFONY__APC__HOST'],
  'SYMFONY__API__FACEBOOK__APP_ID' => ENV['SYMFONY__API__FACEBOOK__APP_ID'],
  'SYMFONY__API__FACEBOOK__APP_SECRET' => ENV['SYMFONY__API__FACEBOOK__APP_SECRET'],
  'SYMFONY__API__FACEBOOK__PROTOCOL' => ENV['SYMFONY__API__FACEBOOK__PROTOCOL'],
  'SYMFONY__API__FACEBOOK__HOST_PORT' => ENV['SYMFONY__API__FACEBOOK__HOST_PORT'],
  'SYMFONY__API__TWITTER__HOST_PORT' => ENV['SYMFONY__API__TWITTER__HOST_PORT'],
  'SYMFONY__API__TWITTER__PROTOCOL' => ENV['SYMFONY__API__TWITTER__PROTOCOL'],
  'SYMFONY__API__TWITTER__CONSUMER_KEY' => ENV['SYMFONY__API__TWITTER__CONSUMER_KEY'],
  'SYMFONY__API__TWITTER__CONSUMER_SECRET' => ENV['SYMFONY__API__TWITTER__CONSUMER_SECRET'],
  'SYMFONY__API__TWITTER__CALLBACK_URL' => ENV['SYMFONY__API__TWITTER__CALLBACK_URL'],
  'SYMFONY__API__TWITTER__USER_TOKEN' => ENV['SYMFONY__API__TWITTER__USER_TOKEN'],
  'SYMFONY__API__TWITTER__USER_SECRET' => ENV['SYMFONY__API__TWITTER__USER_SECRET'],
  'SYMFONY__API__TWITTER__VERSION' => ENV['SYMFONY__API__TWITTER__VERSION'],
  'SYMFONY__ELASTICSEARCH__HOST' => ENV['SYMFONY__ELASTICSEARCH__HOST'],
  'SYMFONY__ELASTICSEARCH__PORT' => ENV['SYMFONY__ELASTICSEARCH__PORT'],
  'SYMFONY__FRAMEWORK__SECRET' => ENV['SYMFONY__FRAMEWORK__SECRET'],
  'SYMFONY__IMAP__USERNAME' => ENV['SYMFONY__IMAP__USERNAME'],
  'SYMFONY__IMAP__PASSWORD' => ENV['SYMFONY__IMAP__PASSWORD'],
  'SYMFONY__MYSQL__DATABASE' => ENV['SYMFONY__MYSQL__DATABASE'],
  'SYMFONY__MYSQL__USER' => ENV['SYMFONY__MYSQL__USER'],
  'SYMFONY__MYSQL__PASSWORD' => ENV['SYMFONY__MYSQL__PASSWORD'],
  'SYMFONY__MYSQL__HOST' => ENV['SYMFONY__MYSQL__HOST'],
  'SYMFONY__MYSQL__PORT' => ENV['SYMFONY__MYSQL__PORT'],
  'SYMFONY__MYSQL__DATABASE_READ' => ENV['SYMFONY__MYSQL__DATABASE_READ'],
  'SYMFONY__MYSQL__USER_READ' => ENV['SYMFONY__MYSQL__USER_READ'],
  'SYMFONY__MYSQL__PASSWORD_READ' => ENV['SYMFONY__MYSQL__PASSWORD_READ'],
  'SYMFONY__MYSQL__HOST_READ' => ENV['SYMFONY__MYSQL__HOST_READ'],
  'SYMFONY__MYSQL__PORT_READ' => ENV['SYMFONY__MYSQL__PORT_READ'],
  'SYMFONY__MYSQL__DATABASE_WRITE' => ENV['SYMFONY__MYSQL__DATABASE_WRITE'],
  'SYMFONY__MYSQL__USER_WRITE' => ENV['SYMFONY__MYSQL__USER_WRITE'],
  'SYMFONY__MYSQL__PASSWORD_WRITE' => ENV['SYMFONY__MYSQL__PASSWORD_WRITE'],
  'SYMFONY__MYSQL__HOST_WRITE' => ENV['SYMFONY__MYSQL__HOST_WRITE'],
  'SYMFONY__MYSQL__PORT_WRITE' => ENV['SYMFONY__MYSQL__PORT_WRITE'],
  'SYMFONY__MYSQL__TEST_DATABASE' => ENV['SYMFONY__MYSQL__TEST_DATABASE'],
  'SYMFONY__MYSQL__TEST_USER' => ENV['SYMFONY__MYSQL__TEST_USER'],
  'SYMFONY__MYSQL__TEST_PASSWORD' => ENV['SYMFONY__MYSQL__TEST_PASSWORD'],
  'SYMFONY__MYSQL__ADMIN_USER' => ENV['SYMFONY__MYSQL__ADMIN_USER'],
  'SYMFONY__MYSQL__ADMIN_PASSWORD' => ENV['SYMFONY__MYSQL__ADMIN_PASSWORD'],
  'SYMFONY__OAUTH__CLIENT_ID' => ENV['SYMFONY__OAUTH__CLIENT_ID'],
  'SYMFONY__OAUTH__CLIENT_SECRET' => ENV['SYMFONY__OAUTH__CLIENT_SECRET'],
  'SYMFONY__OAUTH__ACCESS_TOKEN' => ENV['SYMFONY__OAUTH__ACCESS_TOKEN'],
  'SYMFONY__QUALITY_ASSURANCE__PASSWORD' => ENV['SYMFONY__QUALITY_ASSURANCE__PASSWORD'],
  'SYMFONY__RABBITMQ__USER' => ENV['SYMFONY__RABBITMQ__USER'],
  'SYMFONY__RABBITMQ__PASSWORD' => ENV['SYMFONY__RABBITMQ__PASSWORD'],
  'SYMFONY__RABBITMQ__HOST' => ENV['SYMFONY__RABBITMQ__HOST'],
  'SYMFONY__RABBITMQ__VHOST' => ENV['SYMFONY__RABBITMQ__VHOST'],
}

role :web,                '127.0.0.1'

set :app_config_path,     fetch(:app_path) + '/config'

set :app_path,            'app'

set :application,         'devobs'

set :branch,              'deploy'

set :bundle_gemfile,      -> { release_path.join('Gemfile') }

set :cache_path,          fetch(:app_path) + '/cache'

set :composer_install_flags, '--no-dev --prefer-dist --no-interaction'

set :deploy_to,           '/var/deploy/devobs'

set :format,              :pretty

set :keep_releases,       3

set :log_path,            fetch(:app_path) + '/logs'

set :linked_dirs,         [fetch(:log_path), 'app/var/sessions', 'web/uploads',
                          'src/WeavingTheWeb/Bundle/DashboardBundle/Resources/perspectives/archive',
                          'src/WeavingTheWeb/Bundle/DashboardBundle/Resources/perspectives/upload',
                          'src/WeavingTheWeb/Bundle/DashboardBundle/Resources/perspectives/import',
                          'src/WeavingTheWeb/Bundle/DashboardBundle/Resources/perspectives/export']

set :log_level,           :info

set :linked_files,        %w{app/config/parameters.yml}

set :parameters_dir,      fetch(:app_path) + '/config'

set :parameters_file,     '/parameters.yml.dist'

set :repo_url,            'git@github.com:WeavingTheWeb/devobs.git'

set :rvm1_ruby_version,   "2.2.1"

set :tmp_dir,             '/tmp'

set :web_path,            'web'

set :webserver_user,      'www-data'

set :whenever_identifier, ->{ "#{fetch(:application)}_#{fetch(:stage)}" }

set :whenever_roles,      ->{ :web }

set :ssh_options, {
  forward_agent: true,
  auth_methods: ["publickey"],
  keys: [".vagrant/machines/default/virtualbox/private_key"]
}

SSHKit.config.command_map[:composer] = "php #{shared_path.join('composer.phar')}"

before 'composer:install', 'install_node_modules'

after 'composer:install', 'symfony:install_javascript_routing_assets'

before "deploy:check:linked_files", 'symfony:upload_configuration_parameters'

before 'symfony:assetic:dump', 'symfony:apply_database_migrations'

before 'symfony:cache:warmup', 'symfony:clear_cache'

before "rvm1:install:rvm", 'rvm:update_rvm_key'

before 'rvm1:install:ruby', 'rvm1:install:rvm'

before 'bundler:install', 'rvm1:install:ruby'

before 'whenever:update_crontab', 'bundler:install'

after 'symfony:cache:warmup', 'install_bower_components'

after 'deploy', 'deploy:cleanup'

after 'deploy', 'symfony:clear_accelerator_cache'

after 'deploy:starting', 'composer:install_executable'

after 'deploy:cleanup_rollback', 'symfony:clear_accelerator_cache'

after 'deploy:updated', 'symfony:assets:install'

after 'deploy:updated', 'symfony:assetic:dump'


