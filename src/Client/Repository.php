<?php

namespace Tuf\Client;

use Tuf\Exception\RepoFileNotFound;
use Tuf\Loader\SizeCheckingLoader;
use Tuf\Metadata\RootMetadata;
use Tuf\Metadata\SnapshotMetadata;
use Tuf\Metadata\TargetsMetadata;
use Tuf\Metadata\TimestampMetadata;

/**
 * Defines a backend to load untrusted TUF metadata objects.
 */
class Repository
{
    /**
     * The maximum number of bytes to download if the remote file size is not
     * known.
     *
     * @var int
     */
    public const MAX_BYTES = 100000;

    public function __construct(private SizeCheckingLoader $sizeCheckingLoader)
    {
    }

    /**
     * Loads untrusted root metadata.
     *
     * @param int $version
     *   The version of the root metadata to load.
     *
     * @return \Tuf\Metadata\RootMetadata|null
     *   An instance of \Tuf\Metadata\RootMetadata, or null if the requested
     *   version of the metadata doesn't exist.
     */
    public function getRoot(int $version): ?RootMetadata
    {
        try {
            $data = $this->sizeCheckingLoader->load("$version.root.json", self::MAX_BYTES);

            return RootMetadata::createFromJson($data->getContents());
        } catch (RepoFileNotFound) {
            // If the next version of the root metadata doesn't exist, it's not
            // an error -- it just means there's nothing newer. So we can safely
            // return null.
            return null;
        }
    }

    /**
     * Loads untrusted timestamp metadata.
     *
     * @return \Tuf\Metadata\TimestampMetadata
     *   The untrusted timestamp metadata.
     */
    public function getTimestamp(): TimestampMetadata
    {
        $data = $this->sizeCheckingLoader->load('timestamp.json', self::MAX_BYTES);

        return TimestampMetadata::createFromJson($data->getContents());
    }

    /**
     * Loads untrusted snapshot metadata.
     *
     * @param int|null $version
     *   The version of the snapshot metadata to load, or null if consistent
     *   snapshots are not used.
     * @param int $maxBytes
     *   The maximum number of bytes to download.
     *
     * @return \Tuf\Metadata\SnapshotMetadata
     *   The untrusted snapshot metadata.
     */
    public function getSnapshot(?int $version, int $maxBytes = self::MAX_BYTES): SnapshotMetadata
    {
        $name = isset($version) ? "$version.snapshot" : 'snapshot';
        $data = $this->sizeCheckingLoader->load("$name.json", $maxBytes);

        return SnapshotMetadata::createFromJson($data->getContents());
    }

    /**
     * Loads untrusted targets metadata for a specific role.
     *
     * @param int|null $version
     *   The version of the targets metadata to load, or null if consistent
     *   snapshots are not used.
     * @param string $role
     *   The role to load. Defaults to `targets`, but could be the name of any
     *   delegated role.
     * @param int $maxBytes
     *   The maximum number of bytes to download.
     *
     * @return \Tuf\Metadata\TargetsMetadata
     *   The untrusted targets metadata.
     */
    public function getTargets(?int $version, string $role = 'targets', int $maxBytes = self::MAX_BYTES): TargetsMetadata
    {
        $name = isset($version) ? "$version.$role" : $role;
        $data = $this->sizeCheckingLoader->load("$name.json", $maxBytes);

        return TargetsMetadata::createFromJson($data->getContents(), $role);
    }
}
