Feature: All main pages should exist

Scenario: Browse pages
  When I go to "/"
  Then the response status code should be 200
  When I go to "/about"
  Then the response status code should be 200
  When I go to "/about"
  Then the response status code should be 200

Scenario: Check upcoming page
  When I go to "/upcoming"
  Then the response status code should be 200
  #And I should see 25 ".list ul" elements
  #And print last response
