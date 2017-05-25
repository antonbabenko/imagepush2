variable "aws_region" {
  description = "AWS region"
}

variable "aws_allowed_account_ids" {
  description = "List of alloed AWS account ids"
  type = "list"
}

variable "vpc_id" {
  description = "VPC id"
}

variable "subnet_ids" {
  description = "List of subnets to use"
  type = "list"
}

# Elastic Beanstalk
variable "eb_application" {
  description = "Application name"
}

variable "eb_environment_name" {
  description = "Environment name"
}

variable "eb_environment_solution_stack_name" {
  description = "Solution stack name in Elastic Beanstalk"
}