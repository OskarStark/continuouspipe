Feature:
  In order to do as less as possible
  As a developer
  I want the configuration of the deployed services to be automaticaly completed from my docker-compose file

  Scenario: I can explicitly defines the source of a component from the built
    Given I have a "continuous-pipe.yml" file in my repository that contains:
    """
    tasks:
        images:
            build:
                services:
                    api:
                        image: foo/bar
                        build_directory: .

        deploy:
            deploy:
                cluster: foo
                services:
                    api: ~
                    worker:
                        specification:
                            source:
                                from_service: api
    """
    When the configuration of the tide is generated for the branch "my-feature" and the commit "de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3"
    Then the generated configuration should contain at least:
    """
    tasks:
        images:
            build:
                services:
                    api:
                        image: foo/bar
                        tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
        deploy:
            deploy:
                cluster: foo
                services:
                    api:
                        specification:
                            source:
                                image: foo/bar
                                tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
                    worker:
                        specification:
                            source:
                                image: foo/bar
                                tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
    """

  Scenario: I can explicitly defines the source of a component from the built
    Given I have a "docker-compose.yml" file in my repository that contains:
    """
    api:
        build: .
        labels:
            com.continuouspipe.image-name: foo/bar
    worker:
        build: .
    """
    Given I have a "continuous-pipe.yml" file in my repository that contains:
    """
    tasks:
        images:
            build:
                services:
                    api:
                        naming_strategy: branch

        deploy:
            deploy:
                cluster: foo
                services:
                    api: ~
                    worker:
                        specification:
                            source:
                                from_service: api
                            environment_variables:
                                - name: FOO
                                  value: BAR
    """
    When the configuration of the tide is generated for the branch "my-feature"
    Then the generated configuration should contain at least:
    """
    tasks:
        images:
            build:
                services:
                    api:
                        image: foo/bar
                        tag: my-feature
        deploy:
            deploy:
                cluster: foo
                services:
                    api:
                        specification:
                            source:
                                image: foo/bar
                                tag: my-feature
                    worker:
                        specification:
                            source:
                                image: foo/bar
                                tag: my-feature
    """

  Scenario: Image name with `from_service` should not impact other services
    Given I have a "docker-compose.yml" file in my repository that contains:
    """
    api:
        build: .
        labels:
            com.continuouspipe.image-name: foo/bar
    worker:
        build: .
        labels:
            com.continuouspipe.image-name: inviqasession/cp-builder
    """
    And I have a flow with the following configuration:
    """
    tasks:
        images:
            build:
                services:
                    api: ~
        deploy:
            deploy:
                cluster: foo
                services:
                    api: ~
                    worker:
                        specification:
                            source:
                                from_service: api
                            environment_variables:
                                - name: FOO
                                  value: BAR
    """
    When a tide is started for the branch "my-feature" and commit "de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3"
    Then the configuration of the tide should contain at least:
    """
    tasks:
        images:
            build:
                services:
                    api:
                        image: foo/bar
                        tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
        deploy:
            deploy:
                cluster: foo
                services:
                    api:
                        specification:
                            source:
                                image: foo/bar
                                tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
                    worker:
                        specification:
                            source:
                                image: foo/bar
                                tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
    """

  Scenario: It creates the configuration with the full qualified name of the image
    Given I have a "docker-compose.yml" file in my repository that contains:
    """
    api:
        build: .
        labels:
            com.continuouspipe.image-name: grc.io/foo/bar
    """
    And I have a flow with the following configuration:
    """
    tasks:
        images:
            build:
                services:
                    api: ~

        deploy:
            deploy:
                cluster: foo
                services:
                    api: ~
    """
    When a tide is started for the branch "my-feature" and commit "de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3"
    Then the configuration of the tide should contain at least:
    """
    tasks:
        images:
            build:
                services:
                    api:
                        image: grc.io/foo/bar
                        tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
        deploy:
            deploy:
                cluster: foo
                services:
                    api:
                        specification:
                            source:
                                image: grc.io/foo/bar
                                tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
    """

  Scenario: The image name is configured in the build
    Given I have a "docker-compose.yml" file in my repository that contains:
    """
    app:
        build: .
        volumes:
            - ./:/app
        expose:
            - 80
    """
    And I have a flow with the following configuration:
    """
    tasks:
        images:
            build:
                services:
                    app:
                        image: docker.io/inviqasession/cp-website

        deploy:
            deploy:
                cluster: fra-01
    """
    When a tide is started for the branch "master" and commit "de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3"
    Then the configuration of the tide should contain at least:
    """
    tasks:
        images:
            build:
                services:
                    app:
                        image: docker.io/inviqasession/cp-website
                        tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
        deploy:
            deploy:
                cluster: fra-01
                services:
                    app:
                        specification:
                            source:
                                image: docker.io/inviqasession/cp-website
                                tag: de3ad3af0a7be847aa6fae2c14caa39eb5c1bee3
    """

  Scenario: The image name is configured in the build
    Given I have a "docker-compose.yml" file in my repository that contains:
    """
    app:
        build: .
        volumes:
            - ./:/app
        expose:
            - 80
    """
    And I have a flow with the following configuration:
    """
    tasks:
        images:
            build:
                services:
                    app:
                        image: docker.io/inviqasession/cp-website
                        naming_strategy: sha1

        deploy:
            deploy:
                cluster: fra-01
    """
    When a tide is started for the branch "master" and commit "3b0110193e36b317207909163d0a582f6f568cf8"
    Then the configuration of the tide should contain at least:
    """
    tasks:
        images:
            build:
                services:
                    app:
                        image: docker.io/inviqasession/cp-website
                        tag: 3b0110193e36b317207909163d0a582f6f568cf8
        deploy:
            deploy:
                cluster: fra-01
                services:
                    app:
                        specification:
                            source:
                                image: docker.io/inviqasession/cp-website
                                tag: 3b0110193e36b317207909163d0a582f6f568cf8
    """

  Scenario: I can explicitly defines the source of a component from the built step, even with steps
    Given I have a "docker-compose.yml" file in my repository that contains:
    """
    api:
        build: .
    worker:
        build: .
    """
    Given I have a "continuous-pipe.yml" file in my repository that contains:
    """
    tasks:
        images:
            build:
                services:
                    api:
                        steps:
                            first:
                                naming_strategy: branch
                                image: foo/bar

        deploy:
            deploy:
                cluster: foo
                services:
                    api: ~
                    worker:
                        specification:
                            source:
                                from_service: api
                            environment_variables:
                                - name: FOO
                                  value: BAR
    """
    When the configuration of the tide is generated for the branch "my-feature"
    Then the generated configuration should contain at least:
    """
    tasks:
        images:
            build:
                services:
                    api:
                        steps:
                            first:
                                tag: my-feature
        deploy:
            deploy:
                cluster: foo
                services:
                    api:
                        specification:
                            source:
                                image: foo/bar
                                tag: my-feature
                    worker:
                        specification:
                            source:
                                image: foo/bar
                                tag: my-feature
    """
