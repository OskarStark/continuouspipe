Feature:
  In order to be able to protect secrets and build tiny images
  As a user
  I want to be able to have a build in many different steps

  Scenario: Uses read & write artifacts
    Given I have a "continuous-pipe.yml" file in my repository that contains:
    """
    tasks:
        images:
            build:
                services:
                    first:
                        steps:
                            - docker_file_path: ./Buildfile
                              write_artifacts:
                                  - name: built-files
                                    path: /dist

                            - docker_file_path: ./Dockerfile
                              image: sroze/image
                              read_artifacts:
                                  - name: built-files
                                    path: /var/www/html
    """
    When a tide is started with the UUID "00000000-0000-0000-0000-000000000000"
    Then the build should be started with 2 steps
    And the step #0 of the build should be started with a write artifact identified "00000000-0000-0000-0000-000000000000-built-files" on path "/dist"
    And the step #1 of the build should be started with a read artifact identified "00000000-0000-0000-0000-000000000000-built-files" on path "/var/www/html"
