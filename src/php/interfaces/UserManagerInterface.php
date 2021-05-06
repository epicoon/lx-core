<?php

namespace lx;

interface UserManagerInterface
{
    public function getUserModelName(): string;
    public function getAuthFieldName(): string;
    public function getPasswordFieldName(): string;

    public function identifyUserById(int $id, ?UserInterface $defaultUser = null): ?UserInterface;

    /**
     * @param mixed $userAuthValue
     */
    public function identifyUserByAuthValue($userAuthValue, ?UserInterface $defaultUser = null): ?UserInterface;
    
    /**
     * @param mixed $userAuthValue
     */
    public function identifyUserByPassword(
        $userAuthValue,
        string $password,
        ?UserInterface $defaultUser = null
    ): ?UserInterface;
    
	public function getPublicData(?UserInterface $user = null): array;

    /**
     * @return array<UserInterface>
     */
	public function getUsers(?int $offset = 0, ?int $limit = null): array;
	
	/**
	 * @param mixed $userAuthValue
	 */
	public function createUser($userAuthValue, string $password, array $fields = []): ?UserInterface;

	/**
	 * @param mixed $userAuthValue
	 */
	public function deleteUser($userAuthValue): void;
}
