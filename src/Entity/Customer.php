<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $gender = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $md5Password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $salt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sha1Password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sha256Password = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dob = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $registeredDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cell = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pictureLarge = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pictureMedium = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pictureThumbnail = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getMd5Password(): ?string
    {
        return $this->md5Password;
    }

    public function setMd5Password(string $md5Password): static
    {
        $this->md5Password = $md5Password;

        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt): static
    {
        $this->salt = $salt;

        return $this;
    }

    public function getSha1Password(): ?string
    {
        return $this->sha1Password;
    }

    public function setSha1Password(?string $sha1Password): static
    {
        $this->sha1Password = $sha1Password;

        return $this;
    }

    public function getSha256Password(): ?string
    {
        return $this->sha256Password;
    }

    public function setSha256Password(?string $sha256Password): static
    {
        $this->sha256Password = $sha256Password;

        return $this;
    }

    public function getDob(): ?\DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(?\DateTimeInterface $dob): static
    {
        $this->dob = $dob;

        return $this;
    }

    public function getRegisteredDate(): ?string
    {
        return $this->registeredDate;
    }

    public function setRegisteredDate(?string $registeredDate): static
    {
        $this->registeredDate = $registeredDate;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCell(): ?string
    {
        return $this->cell;
    }

    public function setCell(?string $cell): static
    {
        $this->cell = $cell;

        return $this;
    }

    public function getNat(): ?string
    {
        return $this->nat;
    }

    public function setNat(?string $nat): static
    {
        $this->nat = $nat;

        return $this;
    }

    public function getPictureLarge(): ?string
    {
        return $this->pictureLarge;
    }

    public function setPictureLarge(?string $pictureLarge): static
    {
        $this->pictureLarge = $pictureLarge;

        return $this;
    }

    public function getPictureMedium(): ?string
    {
        return $this->pictureMedium;
    }

    public function setPictureMedium(?string $pictureMedium): static
    {
        $this->pictureMedium = $pictureMedium;

        return $this;
    }

    public function getPictureThumbnail(): ?string
    {
        return $this->pictureThumbnail;
    }

    public function setPictureThumbnail(?string $pictureThumbnail): static
    {
        $this->pictureThumbnail = $pictureThumbnail;

        return $this;
    }
}
