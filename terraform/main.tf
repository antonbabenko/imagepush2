//terraform {
//  backend "s3" {
//    bucket     = "tf-states.anton"
//    key        = "imagepush2"
//    region     = "eu-west-1"
//    lock_table = "terraform_locks"
//  }
//}

provider "aws" {
  region = "${var.aws_region}"
  allowed_account_ids = ["${var.aws_allowed_account_ids}"]
}
//
//data "template_file" "container_definitions" {
//  template = "${file("${path.root}/container-definitions/jenkins.json.tpl")}"
//
//  vars {
//    registry        = "${aws_ecr_repository.jenkins_master.registry_id}.dkr.ecr.${var.aws_region}.amazonaws.com"
//    tag             = "jenkins-master:${var.jenkins_version}"
//    cpu_reservation = "${var.cpu_reservation}"
//    mem_reservation = "${var.mem_reservation}"
//    dockerfile_sha  = "${sha256(data.template_file.jenkins_master.rendered)}"
//  }
//}

# DynamoDB tables

# AWS Elastic Beanstalk
resource "aws_elastic_beanstalk_application" "imagepush2" {
  name        = "imagepush2"
  description = "imagepush.to web-site"
}

resource "aws_elastic_beanstalk_environment" "prod7-eb" {
  name                = "prod7-eb"
  description         = "prod7-eb"
  application         = "${aws_elastic_beanstalk_application.imagepush2.name}"
  solution_stack_name = "64bit Amazon Linux 2016.09 v2.3.2 running PHP 7.0"

//  setting {
//    namespace = "aws:ec2:vpc"
//    name      = "VPCId"
//    value     = "${var.vpc_id}"
//  }
//
//  setting {
//    namespace = "aws:ec2:vpc"
//    name      = "Subnets"
//    value     = "${join(",", var.subnet_ids)}"
//  }
}