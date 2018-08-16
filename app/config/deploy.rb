
# app/config/deploy.rb

logger.level = Logger::MAX_LEVEL

set :application,       "Dashboard"
set :domain,            "start.weaving-the-web.org"
set :deploy_to,         "/var/services/web/ci/dashboard"
set :user,              "jenkins"

set :scm,               :git
set :repository,        "jenkins@wtw_git:/var/services/www/git/wtw/repos/wtw.git"
set :branch,            "ship_dashboard"
set :ssh_options,       { :forward_agent => true }

set :shared_files,      ["app/config/parameters.yml", "web/dummy.php"]
set :shared_children,   ["app/logs", "src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/components", "vendor"]

set :use_composer,        true
set :dump_assetic_assets, true

set :parameters_dir,    "app/config"
set :parameters_file,   "parameters.yml.dist"

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain, :primary => true       # This may be the same as your `Web` server

set  :use_sudo,   false
set  :keep_releases,  3

set :deploy_via, :remote_cache

task :upload_parameters do
  origin_file = parameters_dir + "/" + parameters_file if parameters_dir && parameters_file
  if origin_file && File.exists?(origin_file)
    relative_path = "app/config/parameters.yml"

    if shared_files && shared_files.include?(relative_path)
      destination_file = shared_path + "/" + relative_path
    else
      destination_file = latest_release + "/" + relative_path
    end
    try_sudo "mkdir -p #{File.dirname(destination_file)}"

    top.upload(origin_file, destination_file)
  end
end

default_run_options[:env] = {
  'SYMFONY__ASSETIC__NODE'                  =>  ENV['SYMFONY__ASSETIC__NODE'],
  'SYMFONY__ASSETIC__MODULES'               =>  ENV['SYMFONY__ASSETIC__MODULES'],
  'SYMFONY__APC__HOST'                      =>  ENV['SYMFONY__APC__HOST'],
  'SYMFONY__API__FACEBOOK__PROTOCOL'        =>  ENV['SYMFONY__API__FACEBOOK__PROTOCOL'],
  'SYMFONY__API__FACEBOOK__HOST_PORT'       =>  ENV['SYMFONY__API__FACEBOOK__HOST_PORT'],
  'SYMFONY__API__TWITTER__HOST_PORT'        =>  ENV['SYMFONY__API__TWITTER__HOST_PORT'],
  'SYMFONY__API__TWITTER__PROTOCOL'         =>  ENV['SYMFONY__API__TWITTER__PROTOCOL'],
  'SYMFONY__API__TWITTER__CONSUMER_KEY'     =>  ENV['SYMFONY__API__TWITTER__CONSUMER_KEY'],
  'SYMFONY__API__TWITTER__CONSUMER_SECRET'  =>  ENV['SYMFONY__API__TWITTER__CONSUMER_SECRET'],
  'SYMFONY__API__TWITTER__CALLBACK_URL'     =>  ENV['SYMFONY__API__TWITTER__CALLBACK_URL'],
  'SYMFONY__API__TWITTER__USER_TOKEN'       =>  ENV['SYMFONY__API__TWITTER__USER_TOKEN'],
  'SYMFONY__API__TWITTER__USER_SECRET'      =>  ENV['SYMFONY__API__TWITTER__USER_SECRET'],
  'SYMFONY__API__TWITTER__VERSION'          =>  ENV['SYMFONY__API__TWITTER__VERSION'],
  'SYMFONY__ELASTICSEARCH__HOST'            =>  ENV['SYMFONY__ELASTICSEARCH__HOST'],
  'SYMFONY__ELASTICSEARCH__PORT'            =>  ENV['SYMFONY__ELASTICSEARCH__PORT'],
  'SYMFONY__MYSQL__DATABASE'                =>  ENV['SYMFONY__MYSQL__DATABASE'],
  'SYMFONY__MYSQL__USER'                    =>  ENV['SYMFONY__MYSQL__USER'],
  'SYMFONY__MYSQL__PASSWORD'                =>  ENV['SYMFONY__MYSQL__PASSWORD'],
  'SYMFONY__MYSQL__HOST'                    =>  ENV['SYMFONY__MYSQL__HOST'],
  'SYMFONY__MYSQL__PORT'                    =>  ENV['SYMFONY__MYSQL__PORT'],
  'SYMFONY__MYSQL__DATABASE_READ'           =>  ENV['SYMFONY__MYSQL__DATABASE_READ'],
  'SYMFONY__MYSQL__USER_READ'               =>  ENV['SYMFONY__MYSQL__USER_READ'],
  'SYMFONY__MYSQL__PASSWORD_READ'           =>  ENV['SYMFONY__MYSQL__PASSWORD_READ'],
  'SYMFONY__MYSQL__HOST_READ'               =>  ENV['SYMFONY__MYSQL__HOST_READ'],
  'SYMFONY__MYSQL__PORT_READ'               =>  ENV['SYMFONY__MYSQL__PORT_READ'],
  'SYMFONY__MYSQL__DATABASE_WRITE'          =>  ENV['SYMFONY__MYSQL__DATABASE_WRITE'],
  'SYMFONY__MYSQL__USER_WRITE'              =>  ENV['SYMFONY__MYSQL__USER_WRITE'],
  'SYMFONY__MYSQL__PASSWORD_WRITE'          =>  ENV['SYMFONY__MYSQL__PASSWORD_WRITE'],
  'SYMFONY__MYSQL__HOST_WRITE'              =>  ENV['SYMFONY__MYSQL__HOST_WRITE'],
  'SYMFONY__MYSQL__PORT_WRITE'              =>  ENV['SYMFONY__MYSQL__PORT_WRITE'],
  'SYMFONY__MYSQL__TEST_DATABASE'           =>  ENV['SYMFONY__MYSQL__TEST_DATABASE'],
  'SYMFONY__MYSQL__TEST_USER'               =>  ENV['SYMFONY__MYSQL__TEST_USER'],
  'SYMFONY__MYSQL__TEST_PASSWORD'           =>  ENV['SYMFONY__MYSQL__TEST_PASSWORD'],
  'SYMFONY__MYSQL__ADMIN_PASSWORD'          =>  ENV['SYMFONY__MYSQL__ADMIN_PASSWORD'],
  'SYMFONY__RABBITMQ__USER'                 =>  ENV['SYMFONY__RABBITMQ__USER'],
  'SYMFONY__RABBITMQ__PASSWORD'             =>  ENV['SYMFONY__RABBITMQ__PASSWORD']
}

namespace :deploy do
  task :create_release_dir, :except => {:no_release => true} do
    run "mkdir -p #{fetch :releases_path}"
  end
end

task :clear_symfony_cache do
  run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} cache:clear -e prod'"
end

task :clear_apc_cache do
  run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} apc:clear -e prod'"
end

task :apply_migrations do
  run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} doctrine:migrations:migrate -e prod'"
end

task :install_node_modules do
  run "#{try_sudo} sh -c 'cd #{latest_release} && npm install -q'"
end

task :install_javascript_dependencies do
  run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} sp:bower:install #{console_options} -e prod'"
  run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} fos:js-routing:dump #{console_options} -e prod'"
  run "sudo /etc/init.d/varnish restart"
end

before "symfony:composer:install", "install_node_modules"
before "symfony:cache:warmup", "clear_symfony_cache"
before "symfony:assetic:dump", "apply_migrations"
before "symfony:assetic:dump", "install_javascript_dependencies"
before "deploy:share_childs", "upload_parameters"
before "deploy", "deploy:create_release_dir"

after "deploy", "deploy:cleanup"
after "deploy", "clear_apc_cache"
