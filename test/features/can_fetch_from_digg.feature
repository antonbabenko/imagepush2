Feature: fetch from Digg
  As a robot I want to fetch correct data from Digg API
Scenario: Get homepage and check status code
  Given I am on "/robot/fetchFromDigg"
  Then I should not get
    """
    408
    """