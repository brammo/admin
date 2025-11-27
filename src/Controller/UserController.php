<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller;

/**
 * User Controller
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class UserController extends AppController
{
    /**
     * Profile action - View and edit logged user details
     *
     * @return \Cake\Http\Response|null|void
     */
    public function profile()
    {
        $this->request->allowMethod(['get', 'post']);

        // Get the logged-in user ID
        /** @var int $userId */
        $userId = $this->Authentication->getIdentityData('id');

        // Load Users table from Auth plugin
        $usersTable = $this->fetchTable('Brammo/Auth.Users');

        // Get the user entity
        $user = $usersTable->get($userId);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // If password is empty, remove it from data to keep the current password
            if (empty($data['password'])) {
                unset($data['password']);
            }

            // Use a custom validation context for updates (password not required)
            $user = $usersTable->patchEntity($user, $data, [
                'validate' => 'default',
            ]);

            if ($usersTable->save($user)) {
                $this->Flash->success(__d('brammo/admin', 'Your profile has been updated.'));

                return $this->redirect(['action' => 'profile']);
            }

            $this->Flash->error(__d('brammo/admin', 'Unable to update your profile. Please try again.'));
        }

        $this->set(compact('user'));
    }
}
