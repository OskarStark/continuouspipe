Feature:
  In order to create small Docker images or to hide some credentials
  As a user
  I want to be able to build an image into different steps and share some artifacts between them

  Background:
    Given I am authenticated
    And there is the bucket "00000000-0000-0000-0000-000000000000"
    And the bucket "00000000-0000-0000-0000-000000000000" contains the Docker Registry credentials
    And the bucket "00000000-0000-0000-0000-000000000000" contains the following github tokens:
      | identifier | token |
      | sroze      | 12345 |

  @integration
  Scenario: I use a build container
    When I send the following build request:
    """
    {
      "credentialsBucket": "00000000-0000-0000-0000-000000000000",
      "steps": [
        {
          "repository": {
            "address": "fixtures://build-container-with-artifacts",
            "branch": "master"
          },
          "context": {
            "docker_file_path": "Buildfile"
          },
          "environment": {
            "TOKEN": "secret-token"
          },
          "write_artifacts": [
            {
              "identifier": "artifact-00000000-0000-0000-0000-000000000000",
              "path": "/app/dist"
            }
          ]
        },
        {
          "repository": {
            "address": "fixtures://build-container-with-artifacts",
            "branch": "master"
          },
          "context": {
            "docker_file_path": "Dockerfile"
          },
          "read_artifacts": [
            {
              "identifier": "artifact-00000000-0000-0000-0000-000000000000",
              "path": "/sub-directory/dist-renamed"
            }
          ],
          "image": {
            "name": "docker.io/continuouspipepublicrobot/test",
            "tag": "build-container-with-artifacts"
          }
        }
      ],
      "engine": {
        "type": "docker"
      }
    }
    """
    Then the build should be successful
    And the file "/var/www/html/index.html" in the image "docker.io/continuouspipepublicrobot/test:build-container-with-artifacts" should contain "SUCCESS"

  Scenario: If the first step fail then it will fail the build
    Given the Docker build will fail because of "something"
    When I send the following build request:
    """
    {
      "credentialsBucket": "00000000-0000-0000-0000-000000000000",
      "steps": [
        {
          "repository": {
            "address": "fixtures://php-example",
            "branch": "master"
          }
        },
        {
          "repository": {
            "address": "fixtures://php-example",
            "branch": "master-second"
          }
        }
      ],
      "engine": {
        "type": "docker"
      }
    }
    """
    Then the build should be failed
    And the step #0 should be failed
    And the step #1 should not be started

  Scenario: Cannot write an artifact outside of the archive
    Given the artifact "artifact-00000000-0000-0000-0000-000000000000" contains the fixtures folder "php-example"
    When I send the following build request:
    """
    {
      "credentialsBucket": "00000000-0000-0000-0000-000000000000",
      "steps": [
        {
          "repository": {
            "address": "fixtures://php-example",
            "branch": "master"
          },
          "read_artifacts": [
            {
              "identifier": "artifact-00000000-0000-0000-0000-000000000000",
              "path": "/../dist-renamed"
            }
          ]
        }
      ],
      "engine": {
        "type": "docker"
      }
    }
    """
    Then the build should be failed

  Scenario: It removes the written artifacts after the builds
    When I send the following build request:
    """
    {
      "credentialsBucket": "00000000-0000-0000-0000-000000000000",
      "steps": [
        {
          "repository": {
            "address": "fixtures://build-container-with-artifacts",
            "branch": "master"
          },
          "context": {
            "docker_file_path": "Buildfile"
          },
          "environment": {
            "TOKEN": "secret-token"
          },
          "write_artifacts": [
            {
              "identifier": "artifact-00000000-0000-0000-0000-000000000000",
              "path": "/app/dist"
            }
          ]
        }
      ],
      "engine": {
        "type": "docker"
      }
    }
    """
    Then the build should be successful
    And the artifact "artifact-00000000-0000-0000-0000-000000000000" should have been deleted

  Scenario: It do not remove the artifacts is read from
    Given the artifact "artifact-00000000-0000-0000-0000-000000000000" contains the fixtures folder "php-example"
    When I send the following build request:
    """
    {
      "credentialsBucket": "00000000-0000-0000-0000-000000000000",
      "steps": [
        {
          "repository": {
            "address": "fixtures://php-example",
            "branch": "master"
          },
          "read_artifacts": [
            {
              "identifier": "artifact-00000000-0000-0000-0000-000000000000",
              "path": "/dist-renamed"
            }
          ]
        }
      ],
      "engine": {
        "type": "docker"
      }
    }
    """
    Then the build should be successful
    And the artifact "artifact-00000000-0000-0000-0000-000000000000" should not have been deleted

  Scenario: It do not remove the persistent artifacts after the builds
    When I send the following build request:
    """
    {
      "credentialsBucket": "00000000-0000-0000-0000-000000000000",
      "steps": [
        {
          "repository": {
            "address": "fixtures://build-container-with-artifacts",
            "branch": "master"
          },
          "context": {
            "docker_file_path": "Buildfile"
          },
          "environment": {
            "TOKEN": "secret-token"
          },
          "write_artifacts": [
            {
              "identifier": "artifact-00000000-0000-0000-0000-000000000000",
              "path": "/app/dist",
              "persistent": true
            }
          ]
        }
      ],
      "engine": {
        "type": "docker"
      }
    }
    """
    Then the build should be successful
    And the artifact "artifact-00000000-0000-0000-0000-000000000000" should not have been deleted
    And a log containing 'Writing artifact "/app/dist"' should be created

  Scenario: It fails if an artifact do not exists and is not persistent
    When I send the following build request:
    """
    {
      "credentialsBucket": "00000000-0000-0000-0000-000000000000",
      "steps": [
        {
          "repository": {
            "address": "fixtures://php-example",
            "branch": "master"
          },
          "read_artifacts": [
            {
              "identifier": "artifact-00000000-0000-0000-0000-000000000000",
              "path": "/dist-renamed"
            }
          ]
        }
      ],
      "engine": {
        "type": "docker"
      }
    }
    """
    Then the build should be failed
    And a log containing 'Artifact "/dist-renamed" not found' should be created

  Scenario: It considers an artifact as empty if it do not exists if it is persistent
    When I send the following build request:
    """
    {
      "credentialsBucket": "00000000-0000-0000-0000-000000000000",
      "steps": [
        {
          "repository": {
            "address": "fixtures://php-example",
            "branch": "master"
          },
          "read_artifacts": [
            {
              "identifier": "artifact-00000000-0000-0000-0000-000000000000",
              "path": "/dist-renamed",
              "persistent": true
            }
          ]
        }
      ],
      "engine": {
        "type": "docker"
      }
    }
    """
    Then the build should be successful
    And a log containing 'Reading artifact "/dist-renamed"' should be created
    And a log containing 'The artifact was not found, considering it empty.' should be created
