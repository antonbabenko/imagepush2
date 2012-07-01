Feature: Fetch from Digg
  As a robot
  I want to fetch correct data using Digg API

Scenario: Execute command and check status code
  Given I am on "/robot/fetchFromDigg"
  Then I should not get
    """
    408
    """