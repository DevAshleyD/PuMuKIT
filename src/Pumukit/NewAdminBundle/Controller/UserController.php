<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\UserUpdateType;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ACCESS_ADMIN_USERS')")
 */
class UserController extends AdminController
{
    public static $resourceName = 'user';
    public static $repoName = User::class;

    public function __construct(DocumentManager $documentManager, PaginationService $paginationService, FactoryService $factoryService, GroupService $groupService, UserService $userService)
    {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService);
    }

    /**
     * Overwrite to check Users creation.
     *
     * @return array|Response
     * @Template("PumukitNewAdminBundle:User:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $users = $this->getResources($request, $criteria);
        $repo = $this->documentManager->getRepository(PermissionProfile::class);
        $profiles = $repo->findAll();

        $origins = $this->documentManager->createQueryBuilder(User::class)->distinct('origin')->getQuery()->execute();

        return ['users' => $users, 'profiles' => $profiles, 'origins' => $origins->toArray()];
    }

    /**
     * Create Action
     * Overwrite to create Person
     * referenced to User.
     *
     * @throws \Exception
     *
     * @return Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(Request $request)
    {
        $user = $this->userService->instantiate();
        $form = $this->getForm($user, $request->getLocale());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->userService->create($user);
                $user = $this->personService->referencePersonIntoUser($user);
            } catch (\Exception $e) {
                throw $e;
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
        }

        return $this->render(
            'PumukitNewAdminBundle:User:create.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Update Action
     * Overwrite to update it with user manager
     * Checks plain password and updates encoded password.
     *
     * @throws \Exception
     *
     * @return Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        $user = $this->findOr404($request);

        $locale = $request->getLocale();
        $form = $this->createForm(UserUpdateType::class, $user, ['translator' => $this->translationService, 'locale' => $locale]);

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if (!$user->isLocal()) {
                        $user = $this->userService->update($user, true, false);
                    } else {
                        $response = $this->isAllowedToBeUpdated($user);
                        if ($response instanceof Response) {
                            return $response;
                        }
                        // false to not flush
                        $userManager->updateUser($user, false);
                        // To update aditional fields added
                        $user = $this->userService->update($user);
                    }
                } catch (\Exception $e) {
                    throw $e;
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
            }
        }

        return $this->render(
            'PumukitNewAdminBundle:User:update.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Delete action.
     *
     * @return bool|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request)
    {
        $userToDelete = $this->findOr404($request);

        $response = $this->isAllowedToBeDeleted($userToDelete);
        if ($response instanceof Response) {
            return $response;
        }

        return parent::deleteAction($request);
    }

    /**
     * Batch Delete action.
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     *
     * @return bool|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        $repo = $this->documentManager->getRepository(User::class);

        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        foreach ($ids as $id) {
            $userToDelete = $repo->find($id);
            $response = $this->isAllowedToBeDeleted($userToDelete);
            if ($response instanceof Response) {
                return $response;
            }
        }

        return parent::batchDeleteAction($request);
    }

    /**
     * Edit groups form.
     *
     * @return array
     * @Template("PumukitNewAdminBundle:User:editgroups.html.twig")
     */
    public function editGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);
        $groups = $this->groupService->findAll();

        return [
            'user' => $user,
            'groups' => $groups,
        ];
    }

    /**
     * Update groups action.
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);

        if ('POST' === $request->getMethod()) {
            $addGroups = $request->get('addGroups', []);
            if ('string' === gettype($addGroups)) {
                $addGroups = json_decode($addGroups, true);
            }
            $deleteGroups = $request->get('deleteGroups', []);
            if ('string' === gettype($deleteGroups)) {
                $deleteGroups = json_decode($deleteGroups, true);
            }

            $this->modifyUserGroups($user, $addGroups, $deleteGroups);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
    }

    /**
     * Get user groups.
     *
     * @return JsonResponse
     */
    public function getGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);

        $addGroups = [];
        $addGroupsIds = [];
        $deleteGroups = [];
        if ('GET' === $request->getMethod()) {
            foreach ($user->getGroups() as $group) {
                $addGroups[$group->getId()] = [
                    'key' => $group->getKey(),
                    'name' => $group->getName(),
                    'origin' => $group->getOrigin(),
                ];
                $addGroupsIds[] = new ObjectId($group->getId());
            }
            $groupsToDelete = $this->groupService->findByIdNotIn($addGroupsIds);
            foreach ($groupsToDelete as $group) {
                $deleteGroups[$group->getId()] = [
                    'key' => $group->getKey(),
                    'name' => $group->getName(),
                    'origin' => $group->getOrigin(),
                ];
            }
        }

        return new JsonResponse(
            [
                'add' => $addGroups,
                'delete' => $deleteGroups,
                'userOrigin' => $user->getOrigin(),
            ]
        );
    }

    /**
     * Gets the criteria values.
     *
     * @param array $criteria
     *
     * @return array
     */
    public function getCriteria($criteria)
    {
        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/user/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/user/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/user/criteria', []);

        $new_criteria = [];
        foreach ($criteria as $property => $value) {
            if ('permissionProfile' == $property) {
                if ('all' != $value) {
                    $new_criteria[$property] = new ObjectId($value);
                }
            } elseif ('origin' == $property) {
                if ('all' != $value) {
                    $new_criteria[$property] = $value;
                }
            } elseif ('' !== $value) {
                $new_criteria[$property] = new Regex($value, 'i');
            }
        }

        return $new_criteria;
    }

    /**
     * Change the permission profiles of a list of users.
     *
     * @return JsonResponse
     */
    public function promoteAction(Request $request)
    {
        $profileRepo = $this->documentManager->getRepository(PermissionProfile::class);
        $usersRepo = $this->documentManager->getRepository(User::class);

        $ids = $request->request->get('ids');
        $profile = $profileRepo->find($request->request->get('profile'));

        if (!$profile) {
            throw $this->createNotFoundException('Profile not found!');
        }

        $users = $usersRepo->findBy(['_id' => ['$in' => $ids]]);

        try {
            foreach ($users as $user) {
                if (!$user->hasRole('ROLE_SUPER_ADMIN')) {
                    $user->setPermissionProfile($profile);
                    $this->userService->update($user, true, false);
                }
            }
        } catch (\Exception $e) {
            throw $this->createAccessDeniedException('Unable to promote user');
        }

        return new JsonResponse(['ok']);
    }

    /**
     * Modify User Groups.
     *
     * @param array $addGroups
     * @param array $deleteGroups
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws \Exception
     */
    private function modifyUserGroups(User $user, $addGroups = [], $deleteGroups = [])
    {
        $groupRepo = $this->documentManager->getRepository(Group::class);

        foreach ($addGroups as $addGroup) {
            $groupsIds = explode('_', $addGroup);
            $groupId = $groupsIds[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $this->userService->addGroup($group, $user, false);
            }
        }

        foreach ($deleteGroups as $deleteGroup) {
            $groupsIds = explode('_', $deleteGroup);
            $groupId = $groupsIds[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $this->userService->deleteGroup($group, $user, false);
            }
        }

        $this->documentManager->flush();
    }

    private function isAllowedToBeDeleted(User $userToDelete)
    {
        $repo = $this->documentManager->getRepository(User::class);

        $loggedInUser = $this->getUser();

        if ($loggedInUser === $userToDelete) {
            return new Response("Can not delete the logged in user '".$loggedInUser->getUsername()."'", 409);
        }
        if (1 === $repo->createQueryBuilder()->getQuery()->execute()->count()) {
            return new Response("Can not delete this unique user '".$userToDelete->getUsername()."'", 409);
        }

        $numberAdminUsers = $this->getNumberAdminUsers();

        if ((1 === $numberAdminUsers) && ($userToDelete->isSuperAdmin())) {
            return new Response("Can not delete this unique admin user '".$userToDelete->getUsername()."'", 409);
        }

        if (null !== $person = $userToDelete->getPerson()) {
            try {
                $this->personService->removeUserFromPerson($userToDelete, $person, true);
            } catch (\Exception $e) {
                return new Response(
                    "Can not delete the user '".$userToDelete->getUsername()."'. ".$e->getMessage(),
                    409
                );
            }
        }

        if (User::ORIGIN_LOCAL !== $userToDelete->getOrigin()) {
            if ($loggedInUser->isSuperAdmin()) {
                return true;
            }

            return new Response("Can not delete the external user '".$userToDelete->getUsername()."'. ", 409);
        }

        return true;
    }

    private function isAllowedToBeUpdated(User $userToUpdate)
    {
        $numberAdminUsers = $this->getNumberAdminUsers();

        if ((1 === $numberAdminUsers)) {
            if (($userToUpdate === $this->getUniqueAdminUser()) && (!$userToUpdate->isSuperAdmin())) {
                return new Response("Can not update this unique admin user '".$userToUpdate->getUsername()."'", 409);
            }
        }
        if (!$userToUpdate->isLocal()) {
            return new Response(
                "Not allowed to update this not local user '".$userToUpdate->getUsername()."'",
                Response::HTTP_BAD_REQUEST
            );
        }

        return true;
    }

    private function getNumberAdminUsers()
    {
        $repo = $this->documentManager->getRepository(User::class);

        return $repo->createQueryBuilder()->where(
            "function(){for ( var k in this.roles ) { if ( this.roles[k] == 'ROLE_SUPER_ADMIN' ) return true;}}"
        )->count()->getQuery()->execute();
    }

    private function getUniqueAdminUser()
    {
        $repo = $this->documentManager->getRepository(User::class);

        return $repo->createQueryBuilder()->where(
            "function(){for ( var k in this.roles ) { if ( this.roles[k] == 'ROLE_SUPER_ADMIN' ) return true;}}"
        )->getQuery()->getSingleResult();
    }
}
