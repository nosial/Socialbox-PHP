<?php

    namespace Socialbox\Classes\Configuration;

    class StorageConfiguration
    {
        private string $path;
        private string $userDisplayImagesPath;
        private int $userDisplayImagesMaxSize;

        /**
         * Constructor method to initialize the class properties with provided data.
         *
         * @param array $data An associative array containing configuration values
         */
        public function __construct(array $data)
        {
            $this->path = $data['path'];
            $this->userDisplayImagesPath = $data['user_display_images_path'];
            $this->userDisplayImagesMaxSize = $data['user_display_images_max_size'];
        }

        /**
         * Retrieves the base path value.
         *
         * @return string The base path.
         */
        public function getPath(): string
        {
            return $this->path;
        }

        /**
         * Retrieves the path for user display images.
         *
         * @return string The path where user display images are stored.
         */
        public function getUserDisplayImagesPath(): string
        {
            return $this->path . DIRECTORY_SEPARATOR . $this->userDisplayImagesPath;
        }

        /**
         * Retrieves the maximum size allowed for user display images.
         *
         * @return int The maximum size in bytes.
         */
        public function getUserDisplayImagesMaxSize(): int
        {
            return $this->userDisplayImagesMaxSize;
        }
    }