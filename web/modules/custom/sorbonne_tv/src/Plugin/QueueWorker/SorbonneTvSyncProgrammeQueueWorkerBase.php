<?php

namespace Drupal\sorbonne_tv\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;




class SorbonneTvSyncProgrammeQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public static function create (ContainerInterface $container , array $configuration , $plugin_id , $plugin_definition)
    {
        return new static( $configuration , $plugin_id , $plugin_definition );
    }

    /**
     * {@inheritdoc}
     */
    public function processItem ($item)
    {

        $filename = $item->date.'.json';
        $directory = 'private://sorbonne-tv/programmes/';
        $file_system = \Drupal::service('file_system');
        $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
        $file_repository = \Drupal::service('file.repository');
        $newFilePath = $directory . $filename;

        $file_value = $item->programme;
        unlink($newFilePath);
        $file_repository->writeData($file_value, $newFilePath, FileSystemInterface::EXISTS_REPLACE);


    }
}