namespace :symfony do
  desc "Clear accelerator cache"
  task :clear_accelerator_cache do
    invoke 'symfony:console', 'cache:accelerator:clear', '--env=prod'
  end

  desc "Clear cache"
  task :clear_cache do
    invoke 'symfony:console', 'cache:clear', '--env=prod'
  end

  desc "Apply database migrations"
  task :apply_database_migrations do
    invoke 'symfony:console', 'doctrine:migrations:migrate', '--env=prod'
  end

  desc "Install JavaScript routing assets"
  task :install_javascript_routing_assets do
    invoke 'symfony:console', 'fos:js-routing:dump', '--env=prod'
    on roles(:all) do
      info capture('sudo /etc/init.d/varnish restart')
    end
  end

  desc "Upload configuration parameters"
  task :upload_configuration_parameters do
    on roles(:all) do
      set :origin_file, "#{fetch(:parameters_dir)}#{fetch(:parameters_file)}"

      if fetch(:origin_file) && File.exists?(fetch(:origin_file))
        set :relative_path, "app/config/parameters.yml"
        set :destination_file, "#{shared_path}/#{fetch(:relative_path)}"
        file = File.open("#{fetch(:relative_path)}.dist")
        upload! file, "#{fetch(:destination_file)}"
      end
    end
  end
end
