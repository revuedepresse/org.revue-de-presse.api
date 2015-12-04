# Packaging

Login to [atlas.hashicorp.com](http://atlas.hashicorp.com) after having creating an account and   
start the [tutorial to package a vagrant box for distribution](https://atlas.hashicorp.com/tutorial/packer-vagrant)
using `Packer`.

Download Packer for your workstation from [https://releases.hashicorp.com/packer/](https://releases.hashicorp.com/packer/)
 
The Atlas authorization API token provided by the tutorial will need to be exported 
as an environment variable

```
export ATLAS_TOKEN="Your Atlas authorization token"
```

Check your access token by accessing the URL provided by [Atlas Tutorial](https://atlas.hashicorp.com/tutorial/packer-vagrant)


```
curl "https://atlas.hashicorp.com/ui/tutorial/check?access_token=$ATLAS_TOKEN" 
```

Provide the build name in the next step of the tutorial: `devobs-development`

Push Packer template to Atlas

```
cd provisioning/packaging
export ATLAS_USERNAME='Your Atlas username'
packer push -name $ATLAS_USERNAME/devobs-development template.json
```
