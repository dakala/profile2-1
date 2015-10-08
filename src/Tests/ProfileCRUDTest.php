<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileCRUDTest.
 */

namespace Drupal\profile\Tests;

use \Drupal\KernelTests\KernelTestBase;

/**
 * Tests basic CRUD functionality of profiles.
 *
 * @group profile
 */
class ProfileCRUDTest extends KernelTestBase {

  public static $modules = ['system', 'field', 'entity_reference', 'field_sql_storage', 'user', 'profile'];

  function setUp() {
    parent::setUp();
    $this->installSchema('system', 'url_alias');
    $this->installSchema('system', 'sequences');
    $this->installSchema('user', 'users_data');
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->enableModules(['field', 'entity_reference', 'user', 'profile']);
  }

  /**
   * Tests CRUD operations.
   */
  function testCRUD() {
    $types_data = [
      'profile_type_0' => ['label' => $this->randomMachineName()],
      'profile_type_1' => ['label' => $this->randomMachineName()],
    ];
    foreach ($types_data as $id => $values) {
      $types[$id] = \Drupal::entityManager()->getStorage('profile_type')->create(['id' => $id] + $values);
      $types[$id]->save();
    }
    $this->user1 = \Drupal::entityManager()->getStorage('user')->create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->user1->save();
    $this->user2 = \Drupal::entityManager()->getStorage('user')->create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->user2->save();

    // Create a new profile.
    $profile = \Drupal::entityManager()->getStorage('profile')->create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $this->assertSame($profile->id(), NULL);
    $this->assertTrue($profile->uuid());
    $this->assertSame($profile->getType(), $expected['type']);
    $this->assertSame($profile->label(), t('@type profile of @username (uid: @uid)',
      [
        '@type' => $types['profile_type_0']->label(),
        '@username' => $this->user1->getUsername(),
        '@uid' => $this->user1->id(),
      ])
    );
    $this->assertSame($profile->getOwnerId(), $this->user1->id());
    $this->assertSame($profile->getCreatedTime(), REQUEST_TIME);
    $this->assertSame($profile->getChangedTime(), REQUEST_TIME);

    // Save the profile.
    $status = $profile->save();
    $this->assertSame($status, SAVED_NEW);
    $this->assertTrue($profile->id());
    $this->assertSame($profile->getChangedTime(), REQUEST_TIME);

    // List profiles for the user and verify that the new profile appears.
    $list = \Drupal::entityManager()->getStorage('profile')->loadByProperties([
      'uid' => $this->user1->id(),
    ]);

    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, [(int) $profile->id()]);

    // Reload and update the profile.
    $profile = \Drupal::entityManager()->getStorage('profile')->load($profile->id());
    $profile->setChangedTime($profile->getChangedTime() - 1000);
    $original = clone $profile;
    $status = $profile->save();
    $this->assertSame($status, SAVED_UPDATED);
    $this->assertSame($profile->id(), $original->id());
    $this->assertEquals($profile->getCreatedTime(), REQUEST_TIME);
    $this->assertEquals($original->getChangedTime(), REQUEST_TIME - 1000);
    $this->assertEquals($profile->getChangedTime(), REQUEST_TIME);

    // Create a second profile.
    $user1_profile1 = $profile;
    $profile = \Drupal::entityManager()->getStorage('profile')->create([
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $status = $profile->save();
    $this->assertSame($status, SAVED_NEW);
    $user1_profile = $profile;

    // List profiles for the user and verify that both profiles appear.
    $list = \Drupal::entityManager()->getStorage('profile')->loadByProperties([
      'uid' => $this->user1->id(),
    ]);
    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, [
      (int) $user1_profile1->id(),
      (int) $user1_profile->id(),
    ]);

    // Delete the second profile and verify that the first still exists.
    $user1_profile->delete();
    $this->assertFalse(\Drupal::entityManager()->getStorage('profile')->load($user1_profile->id()));
    $list = \Drupal::entityManager()->getStorage('profile')->loadByProperties([
        'uid' => (int) $this->user1->id(),
      ]);

    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, [(int) $user1_profile1->id()]);

    // Create a new second profile.
    $user1_profile = \Drupal::entityManager()->getStorage('profile')->create([
      'type' => $types['profile_type_1']->id(),
      'uid' => $this->user1->id(),
    ]);
    $status = $user1_profile->save();
    $this->assertSame($status, SAVED_NEW);

    // Create a profile for the second user.
    $user2_profile1 = \Drupal::entityManager()->getStorage('profile')->create([
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user2->id(),
    ]);
    $status = $user2_profile1->save();
    $this->assertSame($status, SAVED_NEW);

    // Delete the first user and verify that all of its profiles are deleted.
    $this->user1->delete();
    $this->assertFalse(\Drupal::entityManager()->getStorage('user')->load($this->user1->id()));
    $list = \Drupal::entityManager()->getStorage('profile')->loadByProperties([
        'uid' => $this->user1->id(),
      ]);
    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, []);

    // List profiles for the second user and verify that they still exist.
    $list = \Drupal::entityManager()->getStorage('profile')->loadByProperties([
        'uid' => $this->user2->id(),
      ]);

    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, [(int) $user2_profile1->id()]);

    // @todo Rename a profile type; verify that existing profiles are updated.
  }

}
