# jenkins-master

This layer is used to deploy a new Jenkins master in an AWS region, or to upgrade an existing image.

## Requirements

This layer requires two non-standard Terraform providers to be present on the deployer's workstation.

https://github.com/mcoffin/terraform-provider-localfile
https://github.com/diosmosis/terraform-provider-docker-image

Build them and place to a directory on your workstation (example `/usr/local/bin/terraform-plugins`)

Add the following lines to  `~/.terraformrc` (create this file if it doesn't already exist)
```js
providers {
  dockerimage = "/usr/local/bin/terraform-plugins/terraform-provider-docker-image"
  localfile = "/usr/local/bin/terraform-plugins/terraform-provider-localfile"
}
```

## First time deployment

```sh
terraform init

terraform plan
terraform apply
```

## Upgrades

1. Upgrades are disruptive, and should be coordinated to run at a time when no jobs are in process

2. In order to deploy a new version of Jenkins, one must follow these steps:

- edit `tf-infra/eu-west-1/jenkins/jenkins-master/terraform.tfvars`
```js
jenkins_version = "2.32.1"  //modify the version
```

- increase the desired capacity to 2
```js
desired_capacity = 2 //creates a second EC2 Container instance for the upgrade
```

- `terraform plan` and `terraform apply`

- continue to check the Jenkins server until the version number is upgraded (lower right corner of landing page)

- decrease the desired capacity to 1
```js
desired_capacity = 1 //removes EC2 container instance
```

- `terraform plan` and `terraform apply`
