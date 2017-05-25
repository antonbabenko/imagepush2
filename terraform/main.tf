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

# Create these resources:
# //DynamoDB tables
# S3 buckets
# Cloudfront
# IAM instance profiles
# Security groups
# SES user
# Route53
# Complete VPC
# Save remote state file to the bucket

######

resource "aws_s3_bucket" "i" {
  bucket = "i.imagepush.to"
  force_destroy = false
}

resource "aws_cloudfront_distribution" "cdn" {
  enabled = true
  comment = "${var.layer_version}"

  aliases = [
    "cdn.imagepush.to",
  ]

  price_class = "PriceClass_All"

  retain_on_delete = true

  origin {
    domain_name = "${aws_elastic_beanstalk_environment.prod7-eb.cname}"
    origin_id   = "prod7-eb"

    custom_origin_config {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "http-only"
      origin_ssl_protocols   = ["SSLv3", "TLSv1"]
    }
  }

  default_cache_behavior {
    allowed_methods = [
      "DELETE",
      "GET",
      "HEAD",
      "OPTIONS",
      "PATCH",
      "POST",
      "PUT",
    ]

    cached_methods = [
      "GET",
      "HEAD",
      "OPTIONS",
    ]

    target_origin_id = "zuul-${var.layer_version}-elb"

    forwarded_values {
      query_string = true

      cookies {
        forward = "all"
      }
    }

    viewer_protocol_policy = "allow-all"
    min_ttl                = 0
    default_ttl            = 3600
    max_ttl                = 86400
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    acm_certificate_arn      = "${lookup(var.parameters, "cloudfront_certificate_arn")}"
    ssl_support_method       = "sni-only"
    minimum_protocol_version = "TLSv1"
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "zuul" {
  zone_id = "${data.terraform_remote_state.shared.environment_domain_id}"
  name    = "${replace("${lookup(var.parameters, "service")}-${var.layer_version}.${data.terraform_remote_state.shared.environment_domain}", "/[^0-9a-zA-Z-.]+/", "")}"
  type    = "A"

  alias {
    name                   = "${aws_cloudfront_distribution.zuul.domain_name}"
    zone_id                = "${aws_cloudfront_distribution.zuul.hosted_zone_id}"
    evaluate_target_health = true
  }

  lifecycle {
    create_before_destroy = true
  }
}

# DynamoDB tables
resource "aws_dynamodb_table" "counter" {
  name           = "counter"
  read_capacity  = 1
  write_capacity = 1
  hash_key       = "key"

  attribute {
    name = "key"
    type = "S"
  }

  tags {
    Name        = "counter"
  }
}

resource "aws_dynamodb_table" "images" {
  name           = "images"
  read_capacity  = 10
  write_capacity = 5
  hash_key       = "id"

  attribute {
    name = "id"
    type = "N"
  }

  attribute {
    name = "isAvailable"
    type = "N"
  }

  attribute {
    name = "requireUpdateTags"
    type = "N"
  }

  attribute {
    name = "tagsFoundCount"
    type = "N"
  }

  attribute {
    name = "timestamp"
    type = "N"
  }

  global_secondary_index {
    name               = "isAvailable-timestamp-index"
    hash_key           = "isAvailable"
    range_key          = "timestamp"
    write_capacity     = 5
    read_capacity      = 1
    projection_type    = "ALL"
  }

  global_secondary_index {
    name               = "requireUpdateTags-timestamp-index"
    hash_key           = "requireUpdateTags"
    range_key          = "timestamp"
    write_capacity     = 1
    read_capacity      = 1
    projection_type    = "ALL"
  }

  global_secondary_index {
    name               = "isAvailable-tagsFoundCount-index"
    hash_key           = "isAvailable"
    range_key          = "tagsFoundCount"
    write_capacity     = 1
    read_capacity      = 1
    projection_type    = "ALL"
  }

  tags {
    Name        = "images"
  }
}

resource "aws_dynamodb_table" "images_tags" {
  name           = "images_tags"
  read_capacity  = 1
  write_capacity = 1
  hash_key       = "key"
  range_key = "id"

  attribute {
    name = "id"
    type = "N"
  }

  attribute {
    name = "key"
    type = "B"
  }

  attribute {
    name = "tag"
    type = "S"
  }

  global_secondary_index {
    name               = "tag-id-index"
    hash_key           = "tag"
    range_key          = "id"
    write_capacity     = 1
    read_capacity      = 1
    projection_type    = "ALL"
  }

  tags {
    Name        = "images_tags"
  }
}

resource "aws_dynamodb_table" "latest_tags" {
  name           = "latest_tags"
  read_capacity  = 1
  write_capacity = 1
  hash_key       = "id"
  range_key = "timestamp"

  attribute {
    name = "id"
    type = "B"
  }

  attribute {
    name = "timestamp"
    type = "N"
  }

  local_secondary_index {
    name               = "id-timestamp-index"
    hash_key           = "id"
    range_key          = "timestamp"
    projection_type    = "ALL"
  }

  tags {
    Name        = "latest_tags"
  }
}

resource "aws_dynamodb_table" "links" {
  name           = "links"
  read_capacity  = 1
  write_capacity = 1
  hash_key       = "link"

  attribute {
    name = "link"
    type = "S"
  }

  tags {
    Name        = "links"
  }
}

resource "aws_dynamodb_table" "processed_hashes" {
  name           = "processed_hashes"
  read_capacity  = 1
  write_capacity = 1
  hash_key       = "hash"

  attribute {
    name = "hash"
    type = "S"
  }

  tags {
    Name        = "processed_hashes"
  }
}

resource "aws_dynamodb_table" "tags" {
  name           = "tags"
  read_capacity  = 1
  write_capacity = 1
  hash_key       = "text"

  attribute {
    name = "text"
    type = "S"
  }

  tags {
    Name        = "tags"
  }
}

# AWS Elastic Beanstalk
resource "aws_elastic_beanstalk_application" "imagepush2" {
  name        = "imagepush2"
  description = "imagepush.to web-site"
}

resource "aws_elastic_beanstalk_environment" "default" {
  name                = "${var.eb_environment_name}"
  description         = "${var.eb_environment_name}"
  application         = "${aws_elastic_beanstalk_application.imagepush2.name}"
  solution_stack_name = "${var.eb_environment_solution_stack_name}"

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