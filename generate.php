<?php
$index_content = <<<EOF
<?php
namespace phgram;
const version = '%s'; # actually a date

require_once 'misc.functions.php';
require_once 'bot.errorhandler.php';
require_once 'debug.function.php';
require_once 'arrayobj.class.php';
require_once 'methodresult.class.php';
require_once 'bot.class.php';

# Objects
require_once 'Objects/base.php';
require_once 'Objects/Animation.php';
require_once 'Objects/Audio.php';
require_once 'Objects/CallbackGame.php';
require_once 'Objects/CallbackQuery.php';
require_once 'Objects/Chat.php';
require_once 'Objects/ChatMember.php';
require_once 'Objects/ChatPermissions.php';
require_once 'Objects/ChatPhoto.php';
require_once 'Objects/ChosenInlineResult.php';
require_once 'Objects/Contact.php';
require_once 'Objects/Document.php';
require_once 'Objects/EncryptedCredentials.php';
require_once 'Objects/EncryptedPassportElement.php';
require_once 'Objects/File.php';
require_once 'Objects/ForceReply.php';
require_once 'Objects/Game.php';
require_once 'Objects/GameHighScore.php';
require_once 'Objects/InlineKeyboardButton.php';
require_once 'Objects/InlineKeyboardMarkup.php';
require_once 'Objects/InlineQuery.php';
require_once 'Objects/InlineQueryResultArticle.php';
require_once 'Objects/InlineQueryResultAudio.php';
require_once 'Objects/InlineQueryResultCachedAudio.php';
require_once 'Objects/InlineQueryResultCachedDocument.php';
require_once 'Objects/InlineQueryResultCachedGif.php';
require_once 'Objects/InlineQueryResultCachedMpeg4Gif.php';
require_once 'Objects/InlineQueryResultCachedPhoto.php';
require_once 'Objects/InlineQueryResultCachedSticker.php';
require_once 'Objects/InlineQueryResultCachedVideo.php';
require_once 'Objects/InlineQueryResultCachedVoice.php';
require_once 'Objects/InlineQueryResultContact.php';
require_once 'Objects/InlineQueryResultDocument.php';
require_once 'Objects/InlineQueryResultGame.php';
require_once 'Objects/InlineQueryResultGif.php';
require_once 'Objects/InlineQueryResultLocation.php';
require_once 'Objects/InlineQueryResultMpeg4Gif.php';
require_once 'Objects/InlineQueryResultPhoto.php';
require_once 'Objects/InlineQueryResultVenue.php';
require_once 'Objects/InlineQueryResultVideo.php';
require_once 'Objects/InputContactMessageContent.php';
require_once 'Objects/InputLocationMessageContent.php';
require_once 'Objects/InputMediaAnimation.php';
require_once 'Objects/InputMediaAudio.php';
require_once 'Objects/InputMediaDocument.php';
require_once 'Objects/InputMediaPhoto.php';
require_once 'Objects/InputMediaVideo.php';
require_once 'Objects/InputMessageContent.php';
require_once 'Objects/InputTextMessageContent.php';
require_once 'Objects/InputVenueMessageContent.php';
require_once 'Objects/Invoice.php';
require_once 'Objects/KeyboardButton.php';
require_once 'Objects/LabeledPrice.php';
require_once 'Objects/Location.php';
require_once 'Objects/LoginUrl.php';
require_once 'Objects/MaskPosition.php';
require_once 'Objects/Message.php';
require_once 'Objects/MessageEntity.php';
require_once 'Objects/OrderInfo.php';
require_once 'Objects/PassportData.php';
require_once 'Objects/PassportElementErrorDataField.php';
require_once 'Objects/PassportElementErrorFile.php';
require_once 'Objects/PassportElementErrorFiles.php';
require_once 'Objects/PassportElementErrorFrontSide.php';
require_once 'Objects/PassportElementErrorReverseSide.php';
require_once 'Objects/PassportElementErrorSelfie.php';
require_once 'Objects/PassportElementErrorTranslationFile.php';
require_once 'Objects/PassportElementErrorTranslationFiles.php';
require_once 'Objects/PassportElementErrorUnspecified.php';
require_once 'Objects/PassportFile.php';
require_once 'Objects/PhotoSize.php';
require_once 'Objects/Poll.php';
require_once 'Objects/PollOption.php';
require_once 'Objects/PreCheckoutQuery.php';
require_once 'Objects/ReplyKeyboardMarkup.php';
require_once 'Objects/ReplyKeyboardRemove.php';
require_once 'Objects/ResponseParameters.php';
require_once 'Objects/ShippingAddress.php';
require_once 'Objects/ShippingOption.php';
require_once 'Objects/ShippingQuery.php';
require_once 'Objects/Sticker.php';
require_once 'Objects/StickerSet.php';
require_once 'Objects/SuccessfulPayment.php';
require_once 'Objects/Update.php';
require_once 'Objects/User.php';
require_once 'Objects/UserProfilePhotos.php';
require_once 'Objects/Venue.php';
require_once 'Objects/Video.php';
require_once 'Objects/VideoNote.php';
require_once 'Objects/Voice.php';
require_once 'Objects/WebhookInfo.php';
__HALT_COMPILER();
EOF;

date_default_timezone_set('America/Belem');
$date = new DateTime('now');
$date_str = $date->format(DateTime::RFC3339);
$index_content = sprintf($index_content, $date_str);
file_put_contents('files/index.php', $index_content);

// The php.ini setting phar.readonly must be set to 0
$pharFile = 'phgram.phar';

// clean up
if (file_exists($pharFile)) {
    unlink($pharFile);
}
if (file_exists($pharFile . '.gz')) {
    unlink($pharFile . '.gz');
}

// create phar
$p = new Phar($pharFile);

// creating our library using whole directory  
$p->buildFromDirectory('files/');

// pointing main file which requires all classes  
$p->setDefaultStub('index.php', '/index.php');

// plus - compressing it into gzip  
$p->compress(Phar::GZ);
   
echo "$pharFile successfully created\n";