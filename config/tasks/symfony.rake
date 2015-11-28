
task :clear_symfony_cache do
  invoke 'symfony:console', 'cache:clear', '--env=prod'
end

task :clear_apc_cache do
  invoke 'symfony:console', 'apc:clear', '--env=prod'
end

task :apply_migrations do
  invoke 'symfony:console', 'doctrine:migrations:migrate', '--env=prod'
end

task :install_javascript_routing do
  invoke 'symfony:console', 'fos:js-routing:dump', '--env=prod'
  on roles(:all) do
    info capture('sudo /etc/init.d/varnish restart')
  end
end

task :upload_parameters do
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
