<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller;

/**
 * User Controller
 *
 * Backwards-compatible alias of UsersController.
 * Host applications that still route to controller `User` keep working.
 */
class UserController extends UsersController
{
}
