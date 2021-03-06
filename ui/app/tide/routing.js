'use strict';

angular.module('continuousPipeRiver')
    .config(function($stateProvider) {
        $stateProvider
            .state('tide', {
                parent: 'flow',
                url: '/:tideUuid',
                abstract: true,
                resolve: {
                    tide: function($stateParams, TideRepository) {
                        return TideRepository.find($stateParams.tideUuid);
                    }
                },
                views: {
                    'title@layout': {
                        template:
                            '<a ui-sref="flows({project: project.slug})">{{ project.name || project.slug }}</a> / '+
                            '<a ui-sref="flow.dashboard({uuid: flow.uuid})">{{ flow.repository.name }}</a> / '+
                            '{{ tide.uuid }} <span class="branch"><md-icon class="cp-icon-git-branch"></md-icon> {{ tide.code_reference.branch }}</span>'
                        ,
                        controller: function($scope, project, flow, tide) {
                            $scope.project = project;
                            $scope.flow = flow;
                            $scope.tide = tide;
                        }
                    }
                }
            })
            .state('tide.logs', {
                url: '/logs',
                views: {
                    'content@': {
                        controller: 'TideLogsController',
                        templateUrl: 'tide/views/logs.html'
                    }
                },
                resolve: {
                    summary: function(TideSummaryRepository, tide) {
                        return TideSummaryRepository.findByTide(tide);
                    }
                },
                aside: false
            })
        ;

        $stateProvider
            .state('kaikai', {
                url: '/kaikai/:tideUuid',
                resolve: {
                    tide: function($stateParams, TideRepository) {
                        return TideRepository.find($stateParams.tideUuid);
                    }
                },
                views: {
                    'content@': {
                        controller: function($state, tide) {
                            $state.go('tide.logs', {
                                project: tide.project.slug,
                                uuid: tide.flow.uuid,
                                tideUuid: tide.uuid
                            });
                        }
                    }
                },
                aside: false
            })
        ;
    });
