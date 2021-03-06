'use strict';

angular.module('continuousPipeRiver')
    .controller('FlowEnvironmentsController', function($scope, $remoteResource, $http, $mdDialog, $componentLogDialog, TideRepository, EnvironmentRepository, EndpointOpener, RemoteShellOpener, flow, user, project) {
        $scope.flow = flow;

        var getEnvironmentStatus = function(environment) {
            if (environment.status == 'Terminating') {
                return 'terminating';
            }

            var status = 'healthy';

            for (var i = 0; i < environment.components.length; i++) {
                var component = environment.components[i];

                if (component.status.status != 'healthy') {
                    status = component.status.status;
                }
            }

            return status;
        };

        var getEnvironmentEndpoints = function(environment) {
            var endpoints = [];

            environment.components.forEach(function(component) {
                component.status.public_endpoints.forEach(function(endpoint) {
                    endpoints.push({
                        name: component.name,
                        address: endpoint
                    });
                })
            });

            return endpoints;
        };

        var loadEnvironments = function() {
            $remoteResource.load('environments', EnvironmentRepository.findByFlow(flow)).then(function (environments) {
                $scope.environments = environments.map(function(environment) {
                    environment.status = getEnvironmentStatus(environment);
                    environment.endpoints = getEnvironmentEndpoints(environment);
                    environment.flow = flow;

                    return environment;
                });
            });
        };

        $scope.isAdmin = user.isAdmin(project);

        $scope.delete = function(environment) {
            swal({
                title: "Are you sure?",
                text: "The environment "+environment.identifier+" won't be recoverable",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, remove it!"
            }).then(function() {
                EnvironmentRepository.delete(flow, environment).then(function () {
                    swal("Deleted!", "Environment successfully deleted.", "success");

                    loadEnvironments();
                }, function (error) {
                    swal("Error !", $http.getError(error) || "An unknown error occurred while deleting the environment", "error");
                });
            }).catch(swal.noop);
        };

        loadEnvironments();

        $scope.openEndpoint = function(endpoint) {
            EndpointOpener.open(endpoint);
        };

        $scope.openRemoteShell = function(environment, component) {
            RemoteShellOpener.open(environment, component);
        };

        $scope.liveStreamComponent = function(environment, component) {
            $componentLogDialog.open($scope, flow, environment, component);
        };

        $scope.deleteContainers = function(environment, component) {
            swal({
                title: "Are you sure?",
                text: "The containers of the component "+component.identifier+" will be deleted. You may lose the state contained in them. If they are backed by deployments, they may be re-created automatically by the cluster.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, remove them!"
            }).then(function() {
                EnvironmentRepository.deleteContainers(flow, environment, component).then(function (containers) {
                    swal("Deleted!", containers.length+" containers successfully deleted.", "success");

                    loadEnvironments();
                }, function (error) {
                    swal("Error !", $http.getError(error) || "An unknown error occurred while deleting the containers", "error");
                });
            }).catch(swal.noop);
        };
    })
    .controller('EnvironmentPreviewController', function($rootScope, $scope, $componentLogDialog, EndpointOpener, environment, flow, $sce) {
        $scope.environment = environment;
        $scope.pointer = true;

        environment.components.forEach(function(component) {
            if (component.status.public_endpoints.length > 0) {
                $scope.url = $sce.trustAsResourceUrl('https://' + component.status.public_endpoints[0]);
            }
        });

        $scope.openEndpoint = function(endpoint) {
            EndpointOpener.open(endpoint);
        };

        $scope.getEnvironmentEndpoints = function(environment) {
            var endpoints = [];

            environment.components.forEach(function(component) {
                component.status.public_endpoints.forEach(function(endpoint) {
                    endpoints.push({
                        name: component.name,
                        address: endpoint
                    });
                })
            });

            return endpoints;
        };

        $scope.$on("angular-resizable.resizeStart", function(e, a) {
            $scope.pointer = false;
        });

        $scope.$on("angular-resizable.resizeEnd", function(e, a) {
            $scope.pointer = true;
        });

        $scope.liveStreamComponent = function(environment, component) {
            $rootScope.$emit('openComponentLogs', component);
        };

        $rootScope.$on('openComponentLogs', function(event, component) {
            $scope.component = component;
        });

        $rootScope.$on('closeComponentLogs', function() {
            $scope.component = null;
        });
    })
    .directive('componentsLogs', function() {
        return {
            restrict: 'E',
            scope: {
                environment: '=',
                component: '='
            },
            controller: 'LogsComponentDialogController',
            templateUrl: 'flow/views/environments/inline/component.html'
        }
    })
    .service('$componentLogDialog', function($mdDialog, $intercom) {
        this.open = function($scope, flow, environment, component) {
            var dialogScope = $scope.$new();
            dialogScope.environment = environment;
            dialogScope.component = component;

            $mdDialog.show({
                templateUrl: 'logs/views/dialogs/component-logs.html',
                parent: angular.element(document.body),
                clickOutsideToClose: true,
                scope: dialogScope
            });

            $intercom.trackEvent('streamed-component-log', {
                environment: environment,
                component: component,
                flow: flow.uuid,
                source: 'environment-list'
            });
        };
    })
;
