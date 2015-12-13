project_dir = '/var/deploy/devobs/current'

every '*/1 * * * *' do
  command "source #{project_dir}/config/scripts/run-job.sh"
end

every '*/30 * * * *' do
  command "source #{project_dir}/config/scripts/produce-user-status-messages.sh"
end
