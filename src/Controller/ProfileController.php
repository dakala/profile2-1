<?php

/**
 * @file
 * Contains \Drupal\profile\Controller\ProfileController.
 */

namespace Drupal\profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\profile\Entity\Profile;
use Drupal\user\UserInterface;

/**
 * Returns responses for ProfileController routes.
 */
class ProfileController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Provides the profile submission form.
   *
   * @param \Drupal\user\UserInterface $type
   *   The user account.
   * @param \Drupal\profile\Entity\ProfileTypeInterface $type
   *   The profile type entity for the profile.
   *
   * @return array
   *   A profile submission form.
   */
  public function addProfile(UserInterface $user, ProfileTypeInterface $type) {

    $profile = $this->entityManager()->getStorage('profile')->create([
      'uid' => $user->id(),
      'type' => $type->id(),
    ]);

    return $this->entityFormBuilder()->getForm($profile, 'add', ['uid' => $user->id(), 'created' => REQUEST_TIME]);
  }

  /**
   * Provides profile delete form.
   *
   * @param $user
   * @param $type
   * @param $id
   *
   * @return array
   */
  public function deleteProfile($user, $type, $id) {
    return $this->entityFormBuilder()->getForm(Profile::load($id), 'delete');
  }

  /**
   * The _title_callback for the entity.profile.add_form route.
   *
   * @param \Drupal\profile\Entity\ProfileTypeInterface $type
   *   The current profile type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(ProfileTypeInterface $type) {
    // @todo: edit profile uses this form too?
    return $this->t('Create @label', ['@label' => $type->label()]);
  }

}
