<?php 

namespace Drupal\ncbs_webcam\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

class WebcamController extends ControllerBase {

  /**
   * Save the webcam image to the file system.
   */
  public function saveImage(Request $request) {
    // Log the start of the image saving process.
    \Drupal::logger('ncbs_webcam')->debug('Starting image saving process.');

    // Get the image data from the request.
    $imageData = $request->request->get('imageData');
    
    // Ensure the image data is provided.
    if (empty($imageData)) {
      \Drupal::logger('ncbs_webcam')->error('No image data found in the request.');
      \Drupal::messenger()->addError('No image data found in the request.');
      return new JsonResponse(['status' => 'error', 'message' => 'No image data found.'], 400);
    }

    try {
      // Decode the base64-encoded image data.
      list($type, $imageData) = explode(';', $imageData);
      list(, $imageData) = explode(',', $imageData);
      $imageData = base64_decode($imageData);

      if ($imageData === false) {
        \Drupal::logger('ncbs_webcam')->error('Failed to decode image data.');
        \Drupal::messenger()->addError('Failed to decode image data.');
        return new JsonResponse(['status' => 'error', 'message' => 'Image data could not be decoded.'], 500);
      }

      // Log successful decoding.
      \Drupal::logger('ncbs_webcam')->debug('Image data decoded successfully.');

      // Generate a unique file name.
      $fileName = 'webcam_' . time() . '.jpg';
      $fileDirectory = 'public://temp_webcam_images';

      // Use the file system service to prepare the directory.
      $file_system = \Drupal::service('file_system');
      if (!$file_system->prepareDirectory($fileDirectory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        \Drupal::logger('ncbs_webcam')->error('Failed to prepare directory @directory.', ['@directory' => $fileDirectory]);
        \Drupal::messenger()->addError('Failed to prepare directory for saving images.');
        return new JsonResponse(['status' => 'error', 'message' => 'Failed to create directory for saving images.'], 500);
      }

      // Log directory preparation.
      \Drupal::logger('ncbs_webcam')->debug('Directory @directory prepared successfully.', ['@directory' => $fileDirectory]);

      // Save the image to the public files directory.
      $filePath = $fileDirectory . '/' . $fileName;
      $fileUri = $file_system->saveData($imageData, $filePath, FileSystemInterface::EXISTS_REPLACE);

      if (!$fileUri) {
        \Drupal::logger('ncbs_webcam')->error('Failed to write image to file system at @filePath.', ['@filePath' => $filePath]);
        \Drupal::messenger()->addError('Failed to write image to file system.');
        return new JsonResponse(['status' => 'error', 'message' => 'Failed to write the image to the file system.'], 500);
      }

      // Log successful file saving.
      \Drupal::logger('ncbs_webcam')->debug('Image saved successfully at @fileUri.', ['@fileUri' => $fileUri]);

      // Create a file entity in Drupal.
      $file = File::create([
        'uri' => $fileUri,
        'status' => 1,  // 1 means the file is permanent.
      ]);
      $file->save();

      // Log the creation of the file entity.
      \Drupal::logger('ncbs_webcam')->debug('File entity created for @fileUri.', ['@fileUri' => $fileUri]);

      // Generate the public URL for the file.
      $fileUrlGenerator = \Drupal::service('file_url_generator');
      $fileUrl = $fileUrlGenerator->generateAbsoluteString($file->getFileUri());

      \Drupal::messenger()->addMessage('Image saved successfully.');
      return new JsonResponse(['status' => 'success', 'file_url' => $fileUrl]);
    } catch (\Exception $e) {
      // Log and show any exceptions that occur.
      \Drupal::logger('ncbs_webcam')->error('Exception during image saving: @message', ['@message' => $e->getMessage()]);
      \Drupal::messenger()->addError('An error occurred: ' . $e->getMessage());
      return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
  }
}
