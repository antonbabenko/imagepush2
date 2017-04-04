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
