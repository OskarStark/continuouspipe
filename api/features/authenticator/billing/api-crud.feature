Feature:
  In order to refer to the billing profiles
  As a user
  I want to be able to create and list my own billing profiles

  Background:
    Given there is a user "samuel"
    And there is a billing profile "00000000-0000-0000-0000-000000000000" for the user "samuel"
    And there is a billing profile "00000000-0000-0000-0000-000000000001" for the user "kieren"

  Scenario: I can create a billing profile
    Given there is a user "dave"
    And I am authenticated as user "dave"
    When I create a billing profile "alternative"
    Then the billing profile "alternative" for "dave" should have been created

  Scenario: I can see my billing profile
    Given I am authenticated as user "samuel"
    When I request my billing profiles
    Then I should see the following billing profiles:
      | uuid                                 |
      | 00000000-0000-0000-0000-000000000000 |

  Scenario: I can see my billing profile
    Given I am authenticated as user "samuel"
    And there is a billing profile "00000000-0000-0000-0000-000000000002" for the user "samuel"
    When I request my billing profiles
    Then I should see the following billing profiles:
      | uuid                                 |
      | 00000000-0000-0000-0000-000000000000 |
      | 00000000-0000-0000-0000-000000000002 |

  Scenario: I can see a specific billing profile
    Given I am authenticated as user "samuel"
    And there is a billing profile "00000000-0000-0000-0000-000000000001" for the user "samuel"
    When I request the billing profile "00000000-0000-0000-0000-000000000001"
    Then I should see that the billing profile is "00000000-0000-0000-0000-000000000001"

  Scenario: I cannot see someone else's specific billing profile
    Given I am authenticated as user "samuel"
    And there is a user "mike"
    And there is a billing profile "00000000-0000-0000-0000-000000000000" for the user "mike"
    When I request the billing profile "00000000-0000-0000-0000-000000000000"
    Then I should be told that I don't have the authorization

  Scenario: No user profile is a 404
    Given I am authenticated as user "unknown"
    When I request my billing profile
    Then I should see the billing profile to be not found

  Scenario: Get a team's billing profile
    Given I am authenticated as user "samuel"
    And there is a team "foo"
    And the user "samuel" is administrator of the team "foo"
    And the team "foo" is linked to the billing profile "00000000-0000-0000-0000-000000000000"
    When I request the billing profile of the team "foo"
    Then I should see that the billing profile is "00000000-0000-0000-0000-000000000000"

  Scenario: Cannot access a team's billing profile if I'm not part of that team
    Given I am authenticated as user "samuel"
    And there is a team "foo"
    And the team "foo" is linked to the billing profile "00000000-0000-0000-0000-000000000000"
    When I request the billing profile of the team "foo"
    Then I should be told that I don't have the authorization

  Scenario: I can delete my billing profile
    Given I am authenticated as user "samuel"
    When I delete the billing profile "00000000-0000-0000-0000-000000000000"
    And I request my billing profiles
    Then I should not see the billing profile "00000000-0000-0000-0000-000000000000"

  Scenario: I can't delete the billing profile of other people
    Given I am authenticated as user "samuel"
    And there is a team "foo"
    And there is a billing profile "00000000-1111-1111-1111-000000000000"
    And the team "foo" is linked to the billing profile "00000000-1111-1111-1111-000000000000"
    When I delete the billing profile "00000000-1111-1111-1111-000000000000"
    Then I should be told that I don't have the authorization to access this billing profile

  @smoke
  Scenario: I can't delete a billing profile linked to a team
    Given I am authenticated as user "samuel"
    And there is a team "foo"
    And there is a billing profile "00000000-0000-0000-0000-000000000000"
    And the team "foo" is linked to the billing profile "00000000-0000-0000-0000-000000000000"
    And the user "samuel" is administrator of the billing profile "00000000-0000-0000-0000-000000000000"
    When I delete the billing profile "00000000-0000-0000-0000-000000000000"
    Then I should be told "The billing profile is linked with some resources that needs to be deleted before" regarding the billing profile
