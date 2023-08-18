<?php

class SKMOBILEAPP_CLASS_ImageValidator extends OW_Validator
{
    protected $fileName;
    protected $required;
    protected $validateImageId;

    public function __construct( $fileName, $validateImageId, $required = false )
    {
        $this->fileName = $fileName;
        $this->required = $required;
        $this->validateImageId = $validateImageId;

        if ( $this->required )
        {
            $errorMessage = OW::getLanguage()->text('base', 'form_validator_required_error_message');
            $this->setErrorMessage($errorMessage);
        }
    }

    public function isValid( $value )
    {
        if ( empty($_FILES[$this->fileName]['name']) && !$this->required )
        {
            return true;
        }

        if ( $this->required && empty($_FILES[$this->fileName]['name']) )
        {
            return false;
        }

        if ( $_FILES[$this->fileName]['error'] != UPLOAD_ERR_OK ) {
            $message = BOL_FileService::getInstance()->getUploadErrorMessage($_FILES[$this->fileName]['error']);
            $this->setErrorMessage($message);

            return false;
        }

        $image = getimagesize($_FILES[$this->fileName]['tmp_name']);

        if ( $image === false )
        {
            $this->errorMessage = OW::getLanguage()->text('skmobileapp', 'invalid_uploaded_image');

            return false;
        }

        return true;
    }

    public function getJsValidator()
    {
        if ( !$this->required )
        {
            return "{
                validate : function( value ){}
            }";
        }

        return ' {
            validate : function( value ){
                    if ( ( !$("' . $this->validateImageId . '").get(0) || !$("' . $this->validateImageId . '").get(0) ) )
                    {
                        if( $.isArray(value) ){ if(value.length == 0  ) throw ' . json_encode($this->getError()) . '; return;}
                        else if( !value || $.trim(value).length == 0 ){ throw ' . json_encode($this->getError()) . '; }
                    }
                },
            getErrorMessage : function(){ return ' . json_encode($this->getError()) . ' }
        } ';
    }
}