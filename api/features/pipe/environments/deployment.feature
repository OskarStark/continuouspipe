Feature:
  In order to have an overview of the deployed environments
  As a developer
  I want to have the list and the status of each environment deployed in different namespaces

  Background:
    Given I am authenticated
    And the bucket of the team "my-team" is the bucket "00000000-0000-0000-0000-000000000000"
    And there is a cluster in the bucket "00000000-0000-0000-0000-000000000000" with the following configuration:
      | identifier | type       | address         | version | username | password |
      | my-cluster | kubernetes | https://1.2.3.4 | v1.4    | username | password |
    And I am building a deployment request
    And the target environment name is "my-environment"
    And the target cluster identifier is "my-cluster"
    And the credentials bucket is "00000000-0000-0000-0000-000000000000"
    And the specification come from the template "simple-app"
    And the environment label "flow" contains "1234567890"
    And the environment label "tide" contains "0987654321"
    And the pods of the deployments will be running after creation
    When I send the built deployment request
    Then the deployment should be successful

  Scenario: Pods not running is unhealthy
    Given pods are pending for the deployment "app"
    When I request the environment list of the cluster "my-cluster" of the team "my-team"
    Then the status of the component "app" should be "unhealthy"

  Scenario: RC pods running is healthy
    Given pods are running for the deployment "app"
    When I request the environment list of the cluster "my-cluster" of the team "my-team"
    Then the status of the component "app" should be "healthy"
