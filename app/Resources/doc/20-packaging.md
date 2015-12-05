# Packaging

New versions of a configuration template and the application can be pushed manually using `Packer` and `Vagrant`.
New builds are started automatically via web hook and GitHub integration with Atlas.

The results of these manual and automated pushes are Atlas artifacts (vagrant boxes) publicly available from
[https://atlas.hashicorp.com/weaving-the-web/boxes/devobs-development](https://atlas.hashicorp.com/weaving-the-web/boxes/devobs-development)

## Build configuration

Login to [atlas.hashicorp.com](http://atlas.hashicorp.com) after having created an account and   
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

Push configuration template and supporting files to Atlas by using Packer

```
cd provisioning/packaging
export ATLAS_USERNAME='Your Atlas username'
export ATLAS_NAME='devobs-development'
export ATLAS_SLUG=$ATLAS_USERNAME/$ATLAS_NAME
packer push -name $ATLAS_SLUG template.json
```

## Application

Push a new version of the application to Atlas by using Vagrant

```
vagrant push atlas
```
