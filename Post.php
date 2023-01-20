<?php

abstract class Post extends Unit
{

    public static function getUploadsDir()
    {
        return static::UPLOAD_DIR;
    }

    public function getFileExtension($file)
    {
        $parts = explode('.', $file);
        $ext = array_pop($parts);
        return $ext;
    }

    public function createUpdate()
    {

        $arrData = $_POST;

        $fileFields =[
            'photo',
            'company_logo',
            'file_path',
            'qr_code_link',
        ];

        foreach ($fileFields as $fileField) {
            if (isset($_FILES[$fileField])) {

                $tableDir = $_SERVER['DOCUMENT_ROOT'] .  '/uploads/' . static::getUploadsDir() ;
                if (!file_exists($tableDir) && !is_dir($tableDir)) {
                    mkdir($tableDir, 0755);
                }

                //определяем имя файла
                $name = str_replace(' ', '', ($_FILES[$fileField]['name']));

                //формируем полный путь загрузки файла
                $uploadFile = static::getUploadsDir()  . '/' . $this->getField('id') . '/' . $name;


                $postDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . static::getUploadsDir() .'/' . $this->getField('id') ;

                //Создаем папку сущности если она не существует
                if (!file_exists($postDir) && !is_dir($postDir)) {
                    mkdir($postDir, 0755);
                }

                //если файл загружен по новому адресу
                if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $uploadFile)) {

                    //сохраняем все адреса файлов
                    $arrData[$fileField] = $uploadFile;

                }
            }
        }



        //пустые массивы для полей и значений
        $fields = [];
        $values = [];

        foreach ($arrData as $key => $value) {
            $fields[] = $key;
            $values[] = $value;
        }

        $this->updateLine($fields,$values);

    }
}

