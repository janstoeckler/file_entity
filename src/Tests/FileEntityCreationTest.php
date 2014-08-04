<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityCreationTest.
 */

namespace Drupal\file_entity\Tests;

/**
 * Tests creating and saving a file.
 *
 * @group file_entity
 */
class FileEntityCreationTest extends FileEntityTestBase {

  public static $modules = array('views');

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('create files', 'edit own document files', 'administer files', 'access files overview', 'administer site configuration', 'view private files'));
    $this->drupalLogin($web_user);
  }

  /**
   * Create a "document" file and verify its consistency in the database.
   * Unset the private folder so it skips the scheme selecting page.
   */
  function testSingleFileEntityCreation() {
    // Configure private file system path
    $config = \Drupal::config('system.file');

    // Unset private file system path so it skips the scheme selecting
    // cause there is only one file system path available (public://) by default.
    $private_file_system_path = NULL;
    $config->set('path.private', $private_file_system_path);
    $this->assertIdentical($config->get('path.private'), $private_file_system_path, 'Private Path is succesfully disabled.');
    $config->save();
    $this->drupalGet('admin/config/media/file-system');

    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Check that the document file has been uploaded.
    $this->assertRaw(t('!type %name was uploaded.', array('!type' => 'Document', '%name' => $test_file->filename)), t('Document file uploaded.'));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'));
  }

  /**
   * Upload a file with both private and public folder set.
   * Should have one extra step selecting a scheme.
   * Selects private scheme and checks if the file is succesfully uploaded to
   * the private folder.
   */
  function testFileEntityCreationMultipleSteps() {
    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Check if your on form step 2, scheme selecting.
    // At this point it should not skip this form.
    $this->assertTrue($this->xpath('//input[@name="scheme"]'), "Loaded select destination scheme page.");

    // Test if the public radio button is selected by default.
    $this->assertFieldChecked('edit-scheme-public', 'Public Scheme is checked');

    // Submit form and set scheme to private
    $edit = array();
    $edit['scheme'] = 'private';
    $this->drupalPostForm(NULL, $edit, t('Next'));

    // Check that the document file has been uploaded.
    $this->assertRaw(t('!type %name was uploaded.', array('!type' => 'Document', '%name' => $test_file->filename)), t('Document file uploaded.'));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'));

    // Check if the file is stored in the private folder.
    $this->assertTrue(substr($file->getFileUri(), 0, 10) === 'private://', 'File uploaded in private folder.');
  }
}