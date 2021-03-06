'use strict';

angular.module('continuousPipeRiver')
    .controller('ProjectUsersController', function($scope, $remoteResource, $http, ProjectMembershipRepository, ProjectRepository, InvitationRepository, project, user) {
        var load = function() {
            $scope.membersStatus = null;
            $remoteResource.load('membersStatus', ProjectRepository.getMembersStatus(project.slug)).then(function (membersStatus) {
                $scope.membersStatus = membersStatus;
            });
        };

        var handleError = function(error) {
            swal("Error !", $http.getError(error) || "An unknown error occured while create the project", "error");
        };

        $scope.removeMembership = function(membership) {
            swal({
                title: "Are you sure?",
                text: "The user "+membership.user.username+" will be removed from the project.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, remove it!"
            }).then(function() {
                ProjectMembershipRepository.remove(project, membership.user).then(load, handleError);
            }).catch(swal.noop);
        };

        $scope.removeInvitation = function(invitation) {
            swal({
                title: "Are you sure?",
                text: "The invitation sent to "+invitation.user_email+" will be cancelled.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, cancel it!"
            }).then(function() {
                InvitationRepository.remove(project, invitation).then(load, handleError);
            }).catch(swal.noop);
        };

        $scope.isAdmin = user.isAdmin(project);

        load();
    });
