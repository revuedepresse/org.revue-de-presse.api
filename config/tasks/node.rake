task :install_node_modules do
  on roles(:all) do
    execute "cd #{release_path} && source ~/.nvm/nvm.sh && npm install -q"
  end
end

task :install_bower_components do
  invoke 'symfony:console', 'sp:bower:install', '--env=prod', '--interactive=false'
end
