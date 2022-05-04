<?php

namespace Drupal\cmis_extensions\Service;

use \Drupal\cmis_extensions\Tree\CouncilFolder;
use \Drupal\cmis_extensions\Tree\EntityFolder;
use \Drupal\cmis_extensions\Tree\KindFolder;
use \Drupal\cmis_extensions\Tree\Kind;

class FoldersProvider {
  private $folders;
  private $councilFolders;

  public function __construct() {
    $this->folders = [
      new EntityFolder('UPHF', '4e693e96-7f7b-45ba-8529-685058ca1610', [
        new KindFolder('4520d1c6-cfd8-44bb-a533-227bfbf07e8a', Kind::Regulations),
        new KindFolder('928ae2df-9a3a-4ed5-83d3-6b42e50ac224', Kind::Statute),
        new KindFolder('183e546f-8286-4e48-a964-cfc77d28b611', Kind::Decree),

        // Conseil de la Formation et de la Vie Etudiante - CFVE
        new KindFolder('c5f54cc7-7734-4c59-94a0-0ec48416b4b3', Kind::MemberList),
        new KindFolder('240ba643-6353-4302-9ff7-6a1906d03f50', Kind::Deliberation),
        new KindFolder('b19ea52c-1360-44b9-a493-9d9a222e8467', Kind::Record),

        // Conseil d'Administration - CA
        new KindFolder('8c0c57ca-7e58-46ac-b601-096e855a2828', Kind::MemberList),
        new KindFolder('bfc7c61e-1cdb-4d86-91cb-bada4b3cd83e', Kind::Deliberation),
        new KindFolder('3311465c-dc0e-480c-9e84-a8e4671ed04b', Kind::Record),

        // Conseil de la Recherche - CoR
        new KindFolder('b363693c-53d4-49ed-9e76-aeb8844c941c', Kind::MemberList),
        new KindFolder('98bbb6b3-bb04-4307-b04f-a2205c37cafa', Kind::Deliberation),
        new KindFolder('a7ca761e-044e-4943-bcfa-71e636537a63', Kind::Record),

        // Comité social d'administration - CSA
        new KindFolder('3ee6e4d8-cd02-46bc-a7e3-f927c4341880', Kind::MemberList),
        new KindFolder('0350f59f-77fd-4141-97f3-b4b6d5473149', Kind::Record)
      ]),

      new EntityFolder('LAMIH UMR CNRS 8201', '6e93be67-532b-4946-92a1-d3d224f1bc48', [
        new KindFolder('a7041d14-61d0-4d2d-91ef-bf35584c077f', Kind::Statute),

        // Conseil de laboratoire
        new KindFolder('c6ca1ccd-28e6-4b83-ac40-475bd4ae0e61', Kind::MemberList),
        new KindFolder('87349c9e-c178-423d-b9b9-98ba48e31e26', Kind::Record),
      ]),

      new EntityFolder('CERAMATHS', 'ffc64981-c180-472b-a18f-6dcde80bcdc3', [
        new KindFolder('35172323-a4c9-4064-8d70-6ed132fc8871', Kind::Statute),

        // Archives / LMI
        new KindFolder('fc536959-17d8-44d7-b925-fc508c4953a1', Kind::Record),
        new KindFolder('424e21c4-42b4-4d2f-84ba-30013397ca48', Kind::Statute),

        // Archives / LAMAV
        new KindFolder('68c60de0-26e9-4a98-8895-5f3979f311ae', Kind::Record),
        new KindFolder('f4a1a116-fa5c-467d-8a05-b372f0e61c7f', Kind::Statute),

        // Archives / LMCPA
        new KindFolder('2e6dc502-4a94-402a-a5ce-4935fefd9550', Kind::Record),
        new KindFolder('fef876fa-4962-4497-98aa-71edd6be0791', Kind::Statute),

        // Conseil de laboratoire
        new KindFolder('8d1903ac-085f-47ac-8494-7d27f99cb955', Kind::MemberList),
        new KindFolder('601f0a62-a252-4f12-a8ab-0df11ca52d79', Kind::Record),
      ]),

      new EntityFolder('LARSH', '7febfebf-8603-4365-a05d-b98b798dd22f', [
        // Archives / DE SCRIPTO
        new KindFolder('a6192556-df8c-4bd9-8b8e-693b68adc9e4', Kind::Record),
        new KindFolder('d8b4230c-34e9-4e53-9185-d06295a02efd', Kind::Statute),

        // Archives / IDP
        new KindFolder('9b40df1f-038e-42cb-bb8f-1c4c7880f35a', Kind::Record),
        new KindFolder('30a74988-d9d2-4773-919a-ce55bf83b259', Kind::Statute),

        // Archives / DE VISU
        new KindFolder('2164310c-879e-414e-999b-98d520d5d9bb', Kind::Record),
        new KindFolder('cb3cbcf8-46dd-4654-910f-f1a729a499d6', Kind::Statute),

        // Archives / CRISS
        new KindFolder('10df4131-b771-4b39-a828-3791b180a616', Kind::Record),
        new KindFolder('719ab2b6-5be5-4946-a6d7-7579d91d5d83', Kind::Statute),

        new KindFolder('10fd95e9-235a-447e-b8c0-02fea80f7193', Kind::Statute),
        new KindFolder('924b2484-092a-4287-a494-822b7246f396', Kind::Statement),

        // Conseil de laboratoire
        new KindFolder('fb54f978-d096-4798-a0ed-11f5e336979f', Kind::MemberList),
        new KindFolder('8256b325-1f26-4584-9368-7786a0da81f5', Kind::Record),

      ]),

      new EntityFolder('IEMN UMR CNRS 8520 Site de Valenciennes', '12ea986b-bd18-4b15-9fc5-fceb001322cc', [
        new KindFolder('d8a8adb9-0e6c-4dca-90d7-6bf5fe8f69af', Kind::Statute)
      ]),

      new EntityFolder('IUT - Institut Universitaire de Technologie', 'ab93464d-3c7b-4e3a-a324-daea1126e32c', [
        new KindFolder('78992032-639e-4ba3-857c-812d77588891', Kind::Decree),
        new KindFolder('328dc1ea-d7a2-41a4-b5ed-47d2bc55918a', Kind::Regulations),
        new KindFolder('47699393-2a90-4b36-9786-9728caf04657', Kind::Statute),

        // Conseil de l'IUT
        new KindFolder('9213434b-5bec-4e6b-bb43-6b1afd4b0bd0', Kind::MemberList),
        new KindFolder('49734c48-ac02-4b48-82f1-446d446bac88', Kind::Record),
      ]),

      new EntityFolder('ISH - Institut Sociétés et Humanités', 'd82eeede-45d1-46e5-a504-2b53d002c2b0', [
        new KindFolder('87c4fc42-b96c-4477-a9eb-f534c5b71847', Kind::Decree),
        new KindFolder('ded81d36-fa4e-458a-ac18-924cf15d6e2d', Kind::Regulations),
        new KindFolder('30ecaf32-4661-42e2-b7b0-a731ec153848', Kind::Statute),

        // Archives / IPAG
        new KindFolder('5753428b-0772-4810-a340-d069c25e162d', Kind::Statute),

        // Archives / FLLASH
        new KindFolder('508eed42-912e-4e43-9d47-2b3c84af7f19', Kind::Statute),

        // Archives / IAE
        new KindFolder('0e42b52b-48c1-4782-9559-227951468ae3', Kind::Statute),

        // Archives / FDEG
        new KindFolder('47e74e7f-07d0-48e8-86a6-ae9fa48ac286', Kind::Statute),

        // Conseil de l'ISH
        new KindFolder('357d6b41-c5a1-473d-811f-523d0550df36', Kind::MemberList),
        new KindFolder('5e4dcc38-1f94-4331-944f-54f964fa83e8', Kind::Record)
      ]),

      new EntityFolder('INSA Hauts-de-France', '60ccd345-a4f0-4138-805f-9ffb274ebd15', [

        new KindFolder('2d536d9d-e65e-40c4-bd6d-54ca8594bc30', Kind::Statute),
        new KindFolder('d46e2d52-af16-4127-9bf1-0cd02ed6f16a', Kind::Decree),
        new KindFolder('ea1fab02-5637-4e18-bb9a-d981ad20f28e', Kind::Regulations),

        // Archives / ISTV
        new KindFolder('92db186c-4924-4199-b929-02ac7aad22e2', Kind::Statement),
        new KindFolder('ebb83d91-3db5-478c-84b3-a7c07a9de5e8', Kind::Statute),

        // Archives / FSMS
        new KindFolder('d63acf15-a5d8-4882-9086-b169f1edae7f', Kind::Statement),
        new KindFolder('1ff07faf-93a8-497b-806b-ff365a17d4ea', Kind::Statute),

        // Archives / ENSIAME
        new KindFolder('ee2c66ab-7908-46cd-9f54-2cdb8f230dd9', Kind::Statement),
        new KindFolder('38c14670-6144-4610-913c-5487894ebdbc', Kind::Statute),

        // Conseil Scientifique
        new KindFolder('fd3bb06a-a78b-44a7-94e1-47da9890f5a6', Kind::MemberList),
        new KindFolder('0c0147b7-bbae-48c9-bdf0-482acff8dddc', Kind::Statement),

        // Conseil des Etudes
        new KindFolder('9e77004a-95f1-455e-a742-0ea362aa5dab', Kind::MemberList),
        new KindFolder('267a5bb3-7992-4645-9563-47938a2e07e5', Kind::Statement),

        // Conseil d'Administration
        new KindFolder('4ac27d75-b9f8-42e5-83fe-6627b72bd6a4', Kind::MemberList),
        new KindFolder('2d2922cc-84c5-47e3-b570-891bfd63b9e2', Kind::Deliberation),
        new KindFolder('6c05e238-e063-4e4a-96d9-538fb57ce672', Kind::Record)
      ])
    ];


    $this->councilFolders = [
      new CouncilFolder(
        'ff47468c-0b65-4d6c-aaf7-ecd866e9be1a',
        'Conseil d\'administration'),
      new CouncilFolder(
        '9dad6747-d438-4c82-a948-a0e7bc89cb91',
        'Conseil de la formation et de la vie étudiante'),
      new CouncilFolder(
        'e108651c-94cb-4f73-b25f-45ecbf810280',
        'Conseil de la recherche')
    ];
  }

  public function getRootFolderId() {
    return "4e693e96-7f7b-45ba-8529-685058ca1610";
  }

  public function entities() {
    return array_map(function($folder) {
      return $folder->getName();
    }, $this->folders);
  }

  public function councils() {
    return array_map(function($folder) {
      return $folder->getName();
    }, $this->councilFolders);
  }

  public function entitiesWithIds() {
    $result = [
      $this->getRootFolderId() => 'Ensemble recueil des actes'
    ];

    foreach ($this->folders as $folder) {
      $result[$folder->getId()] = $folder->getName();
    }

    return $result;
  }

  public function hasEntityFolderFor($id) {
    if ($id == $this->getRootFolderId())
      return true;

    foreach ($this->folders as $folder) {
      if ($folder->getId() == $id)
        return true;
    }

    return false;
  }

  public function possibleFoldersFor($entity, $kind) {
    $folders = $this->folders[$entity]->getKindFolders();

    $filtered = array_filter($folders, function($folder) use ($kind) {
      return $folder->getKind() == $kind;
    });

    return array_map(function($folder) {
      return $folder->getId();
    }, $filtered);
  }

  public function entityFolder($entity) {
    return $this->folders[$entity]->getId();
  }

  public function councilFolder($council) {
    return $this->councilFolders[$council]->getId();
  }

  public function kindFolders($kind) {
    $folders = array_map(function($entityFolder) use ($kind) {
      $children = $entityFolder->getKindFolders();

      $filtered = array_filter($children, function($kindFolder) use ($kind) {
        return $kindFolder->getKind() == $kind;
      });

      return array_map(function($folder) {
        return $folder->getId();
      }, $filtered);
    }, $this->folders);

    return array_merge(...$folders);
  }
}
