<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once "pear.php";
define('PEAR_MP3_ID_FNO', 1);
define('PEAR_MP3_ID_RE', 2);
define('PEAR_MP3_ID_TNF', 3);
define('PEAR_MP3_ID_NOMP3', 4);
class MP3_Id
{
    var $file = false;
    var $id3v1 = false;
    var $id3v11 = false;
    var $id3v2 = false;
    var $name = '';
    var $artists = '';
    var $album = '';
    var $year = '';
    var $comment = '';
    var $track = 0;
    var $genre = '';
    var $genreno = 255;
    var $studied = false;
    var $mpeg_ver = 0;
    var $layer = 0;
    var $bitrate = 0;
    var $crc = false;
    var $frequency = 0;
    var $encoding_type = 0;
    var $samples_per_frame = 0;
    var $samples = 0;
    var $musicsize = -1;
    var $frames = 0;
    var $quality = 0;
    var $padding = false;
    var $private = false;
    var $mode = '';
    var $copyright = false;
    var $original = false;
    var $emphasis = '';
    var $filesize = -1;
    var $frameoffset = -1;
    var $lengthh = false;
    var $length = false;
    var $lengths = false;
    var $error = false;
    var $debug = false;
    var $debugbeg = '<DIV STYLE="margin: 0.5 em; padding: 0.5 em; border-width: thin; border-color: black; border-style: solid">';
    var $debugend = '</DIV>';
    function MP3_Id($study = false)
    {
        if (defined('ID3_SHOW_DEBUG'))
            $this->debug = true;
        $this->study = ($study || defined('ID3_AUTO_STUDY'));
    }
    function read($file = "")
    {
        if ($this->debug)
            print ($this->debugbeg . "id3('$file')<HR>\n");

        if (!empty($file))
            $this->file = $file;
        if ($this->debug)
            print ($this->debugend);

        return $this->_read_v1();
    }
    function setTag($name, $value)
    {
        if (is_array($name))
        {
            foreach ($name as $n => $v)
            {
                $this->$n = $v;
            }
        } else
        {
            $this->$name = $value;
        }
    }

    function getTag($name, $default = 0)
    {
        if (empty($this->$name))
        {
            return $default;
        } else
        {
            return $this->$name;
        }
    }

    function write($v1 = true)
    {
        if ($this->debug)
            print ($this->debugbeg . "write()<HR>\n");
        if ($v1)
        {
            $this->_write_v1();
        }
        if ($this->debug)
            print ($this->debugend);
    }

    function study()
    {
        $this->studied = true;
        $this->_readframe();
    }


    function copy($from)
    {
        if ($this->debug)
            print ($this->debugbeg . "copy(\$from)<HR>\n");
        $this->name = $from->name;
        $this->artists = $from->artists;
        $this->album = $from->album;
        $this->year = $from->year;
        $this->comment = $from->comment;
        $this->track = $from->track;
        $this->genre = $from->genre;
        $this->genreno = $from->genreno;
        if ($this->debug)
            print ($this->debugend);
    }

    function remove($id3v1 = true, $id3v2 = true)
    {
        if ($this->debug)
            print ($this->debugbeg . "remove()<HR>\n");

        if ($id3v1)
        {
            $this->_remove_v1();
        }

        if ($id3v2)
        {

        }

        if ($this->debug)
            print ($this->debugend);
    }


    function _read_v1()
    {
        if ($this->debug)
            print ($this->debugbeg . "_read_v1()<HR>\n");

        $mqr = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);

        if (!($f = @fopen($this->file, 'rb')))
        {
            return PEAR::raiseError("Unable to open " . $this->file, PEAR_MP3_ID_FNO);
        }

        if (fseek($f, -128, SEEK_END) == -1)
        {
            return PEAR::raiseError('Unable to see to end - 128 of ' . $this->file, PEAR_MP3_ID_RE);
        }

        $r = fread($f, 128);
        fclose($f);
        set_magic_quotes_runtime($mqr);

        if ($this->debug)
        {
            $unp = unpack('H*raw', $r);
            print_r($unp);
        }

        $id3tag = $this->_decode_v1($r);

        if (!PEAR::isError($id3tag))
        {
            $this->id3v1 = true;

            $tmp = explode(Chr(0), $id3tag['NAME']);
            $this->name = $tmp[0];

            $tmp = explode(Chr(0), $id3tag['ARTISTS']);
            $this->artists = $tmp[0];

            $tmp = explode(Chr(0), $id3tag['ALBUM']);
            $this->album = $tmp[0];

            $tmp = explode(Chr(0), $id3tag['YEAR']);
            $this->year = $tmp[0];

            $tmp = explode(Chr(0), $id3tag['COMMENT']);
            $this->comment = $tmp[0];

            if (isset($id3tag['TRACK']))
            {
                $this->id3v11 = true;
                $this->track = $id3tag['TRACK'];
            }

            $this->genreno = $id3tag['GENRENO'];
            $this->genre = $id3tag['GENRE'];
        } else
        {
            return $id3tag;
        }

        if ($this->debug)
            print ($this->debugend);
    }

    function _decode_v1($rawtag)
    {
        if ($this->debug)
            print ($this->debugbeg . "_decode_v1(\$rawtag)<HR>\n");

        if ($rawtag[125] == Chr(0) and $rawtag[126] != Chr(0))
        {

            $format = 'a3TAG/a30NAME/a30ARTISTS/a30ALBUM/a4YEAR/a28COMMENT/x1/C1TRACK/C1GENRENO';
        } else
        {

            $format = 'a3TAG/a30NAME/a30ARTISTS/a30ALBUM/a4YEAR/a30COMMENT/C1GENRENO';
        }

        $id3tag = unpack($format, $rawtag);
        if ($this->debug)
            print_r($id3tag);

        if ($id3tag['TAG'] == 'TAG')
        {
            $id3tag['GENRE'] = $this->getgenre($id3tag['GENRENO']);
        } else
        {
            $id3tag = PEAR::raiseError('TAG not found', PEAR_MP3_ID_TNF);
        }
        if ($this->debug)
            print ($this->debugend);
        return $id3tag;
    }

    function _write_v1()
    {
        if ($this->debug)
            print ($this->debugbeg . "_write_v1()<HR>\n");

        $file = $this->file;

        if (!($f = @fopen($file, 'r+b')))
        {
            return PEAR::raiseError("Unable to open " . $file, PEAR_MP3_ID_FNO);
        }

        if (fseek($f, -128, SEEK_END) == -1)
        {
            return PEAR::raiseError("Unable to see to end - 128 of " . $file, PEAR_MP3_ID_RE);
        }

        $this->genreno = $this->getgenreno($this->genre, $this->genreno);

        $newtag = $this->_encode_v1();

        $mqr = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);

        $r = fread($f, 128);

        if (!PEAR::isError($this->_decode_v1($r)))
        {
            if (fseek($f, -128, SEEK_END) == -1)
            {
                return PEAR::raiseError("Unable to see to end - 128 of " . $file, PEAR_MP3_ID_RE);
            }
            fwrite($f, $newtag);
        } else
        {
            if (fseek($f, 0, SEEK_END) == -1)
            {
                return PEAR::raiseError("Unable to see to end of " . $file, PEAR_MP3_ID_RE);
            }
            fwrite($f, $newtag);
        }
        fclose($f);
        set_magic_quotes_runtime($mqr);

        if ($this->debug)
            print ($this->debugend);
    }

    function _encode_v1()
    {
        if ($this->debug)
            print ($this->debugbeg . "_encode_v1()<HR>\n");

        if ($this->track)
        {
            $id3pack = 'a3a30a30a30a4a28x1C1C1';
            $newtag = pack($id3pack, 'TAG', $this->name, $this->artists, $this->album, $this->year, $this->comment, $this->track, $this->genreno);
        } else
        {
            $id3pack = 'a3a30a30a30a4a30C1';
            $newtag = pack($id3pack, 'TAG', $this->name, $this->artists, $this->album, $this->year, $this->comment, $this->genreno);
        }

        if ($this->debug)
        {
            print ('id3pack: ' . $id3pack . "\n");
            $unp = unpack('H*new', $newtag);
            print_r($unp);
        }

        if ($this->debug)
            print ($this->debugend);
        return $newtag;
    }

    function _remove_v1()
    {
        if ($this->debug)
            print ($this->debugbeg . "_remove_v1()<HR>\n");

        $file = $this->file;

        if (!($f = fopen($file, 'r+b')))
        {
            return PEAR::raiseError("Unable to open " . $file, PEAR_MP3_ID_FNO);
        }

        if (fseek($f, -128, SEEK_END) == -1)
        {
            return PEAR::raiseError('Unable to see to end - 128 of ' . $file, PEAR_MP3_ID_RE);
        }

        $mqr = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);

        $r = fread($f, 128);

        $success = false;
        if (!PEAR::isError($this->_decode_v1($r)))
        {
            $size = filesize($this->file) - 128;
            if ($this->debug)
                print ('size: old: ' . filesize($this->file));
            $success = ftruncate($f, $size);
            clearstatcache();
            if ($this->debug)
                print (' new: ' . filesize($this->file));
        }
        fclose($f);
        set_magic_quotes_runtime($mqr);

        if ($this->debug)
            print ($this->debugend);
        return $success;
    }

    function _readframe()
    {
        if ($this->debug)
            print ($this->debugbeg . "_readframe()<HR>\n");

        $file = $this->file;

        $mqr = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);

        if (!($f = fopen($file, 'rb')))
        {
            if ($this->debug)
                print ($this->debugend);
            return PEAR::raiseError("Unable to open " . $file, PEAR_MP3_ID_FNO);
        }

        $this->filesize = filesize($file);

        do
        {
            while (fread($f, 1) != Chr(255))
            {
                if ($this->debug)
                    echo "Find...\n";
                if (feof($f))
                {
                    if ($this->debug)
                        print ($this->debugend);
                    return PEAR::raiseError("No mpeg frame found", PEAR_MP3_ID_NOMP3);
                }
            }
            fseek($f, ftell($f) - 1);

            $frameoffset = ftell($f);

            $r = fread($f, 4);
            $bits = sprintf("%'08b%'08b%'08b%'08b", ord($r{0}), ord($r{1}), ord($r{2}), ord($r{3}));
        } while (!$bits[8] and !$bits[9] and !$bits[10]);
        if ($this->debug)
            print ('Bits: ' . $bits . "\n");

        $this->frameoffset = $frameoffset;

        if ($bits[11] == 0)
        {
            if (($bits[24] == 1) && ($bits[25] == 1))
            {
                $vbroffset = 9;
            } else
            {
                $vbroffset = 17;
            }
        } else
            if ($bits[12] == 0)
            {
                if (($bits[24] == 1) && ($bits[25] == 1))
                {
                    $vbroffset = 9;
                } else
                {
                    $vbroffset = 17;
                }
            } else
            {
                if (($bits[24] == 1) && ($bits[25] == 1))
                {
                    $vbroffset = 17;
                } else
                {
                    $vbroffset = 32;
                }
            }

            fseek($f, ftell($f) + $vbroffset);
        $r = fread($f, 4);

        switch ($r)
        {
            case 'Xing':
                $this->encoding_type = 'VBR';
            case 'Info':

                if ($this->debug)
                    print ('Encoding Header: ' . $r . "\n");

                $r = fread($f, 4);
                $vbrbits = sprintf("%'08b", ord($r{3}));

                if ($this->debug)
                    print ('XING Header Bits: ' . $vbrbits . "\n");

                if ($vbrbits[7] == 1)
                {
                    $r = fread($f, 4);
                    $this->frames = unpack('N', $r);
                    $this->frames = $this->frames[1];
                }

                if ($vbrbits[6] == 1)
                {
                    $r = fread($f, 4);
                    $this->musicsize = unpack('N', $r);
                    $this->musicsize = $this->musicsize[1];
                }

                if ($vbrbits[5] == 1)
                {
                    fseek($f, ftell($f) + 100);
                }

                if ($vbrbits[4] == 1)
                {
                    $r = fread($f, 4);
                    $this->quality = unpack('N', $r);
                    $this->quality = $this->quality[1];
                }

                break;

            case 'VBRI':
            default:
                if ($vbroffset != 32)
                {
                    fseek($f, ftell($f) + 32 - $vbroffset);
                    $r = fread($f, 4);

                    if ($r != 'VBRI')
                    {
                        $this->encoding_type = 'CBR';
                        break;
                    }
                } else
                {
                    $this->encoding_type = 'CBR';
                    break;
                }

                if ($this->debug)
                    print ('Encoding Header: ' . $r . "\n");

                $this->encoding_type = 'VBR';

                fseek($f, ftell($f) + 2);

                fseek($f, ftell($f) + 2);

                $r = fread($f, 2);
                $this->quality = unpack('n', $r);
                $this->quality = $this->quality[1];

                $r = fread($f, 4);
                $this->musicsize = unpack('N', $r);
                $this->musicsize = $this->musicsize[1];


                $r = fread($f, 4);
                $this->frames = unpack('N', $r);
                $this->frames = $this->frames[1];
        }

        fclose($f);
        set_magic_quotes_runtime($mqr);

        if ($bits[11] == 0)
        {
            $this->mpeg_ver = "2.5";
            $bitrates = array('1' => array(0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256, 0), '2' => array(0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0), '3' => array(0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112,
                128, 144, 160, 0), );
        } else
            if ($bits[12] == 0)
            {
                $this->mpeg_ver = "2";
                $bitrates = array('1' => array(0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256, 0), '2' => array(0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0), '3' => array(0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112,
                    128, 144, 160, 0), );
            } else
            {
                $this->mpeg_ver = "1";
                $bitrates = array('1' => array(0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448, 0), '2' => array(0, 32, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 384, 0), '3' => array(0, 32, 40, 48, 56, 64, 80, 96, 112,
                    128, 160, 192, 224, 256, 320, 0), );
            }
            if ($this->debug)
                print ('MPEG' . $this->mpeg_ver . "\n");

        $layer = array(array(0, 3), array(2, 1), );
        $this->layer = $layer[$bits[13]][$bits[14]];
        if ($this->debug)
            print ('layer: ' . $this->layer . "\n");

        if ($bits[15] == 0)
        {
            if ($this->debug)
                print ("protected (crc)\n");
            $this->crc = true;
        }

        $bitrate = 0;
        if ($bits[16] == 1)
            $bitrate += 8;
        if ($bits[17] == 1)
            $bitrate += 4;
        if ($bits[18] == 1)
            $bitrate += 2;
        if ($bits[19] == 1)
            $bitrate += 1;
        $this->bitrate = $bitrates[$this->layer][$bitrate];

        $frequency = array('1' => array('0' => array(44100, 48000), '1' => array(32000, 0), ), '2' => array('0' => array(22050, 24000), '1' => array(16000, 0), ), '2.5' => array('0' => array(11025, 12000), '1' => array(8000, 0), ), );
        $this->frequency = $frequency[$this->mpeg_ver][$bits[20]][$bits[21]];

        $this->padding = $bits[22];
        $this->private = $bits[23];

        $mode = array(array('Stereo', 'Joint Stereo'), array('Dual Channel', 'Mono'), );
        $this->mode = $mode[$bits[24]][$bits[25]];

        $this->copyright = $bits[28];
        $this->original = $bits[29];

        $emphasis = array(array('none', '50/15ms'), array('', 'CCITT j.17'), );
        $this->emphasis = $emphasis[$bits[30]][$bits[31]];

        $samplesperframe = array('1' => array('1' => 384, '2' => 1152, '3' => 1152), '2' => array('1' => 384, '2' => 1152, '3' => 576), '2.5' => array('1' => 384, '2' => 1152, '3' => 576), );
        $this->samples_per_frame = $samplesperframe[$this->mpeg_ver][$this->layer];

        if ($this->encoding_type != 'VBR')
        {
            if ($this->bitrate == 0)
            {
                $s = -1;
            } else
            {
                $s = ((8 * filesize($this->file)) / 1000) / $this->bitrate;
            }
            $this->length = sprintf('%02d:%02d', floor($s / 60), floor($s - (floor($s / 60) * 60)));
            $this->lengthh = sprintf('%02d:%02d:%02d', floor($s / 3600), floor($s / 60), floor($s - (floor($s / 60) * 60)));
            $this->lengths = (int)$s;

            $this->samples = ceil($this->lengths * $this->frequency);
            if (0 != $this->samples_per_frame)
            {
                $this->frames = ceil($this->samples / $this->samples_per_frame);
            } else
            {
                $this->frames = 0;
            }
            $this->musicsize = ceil($this->lengths * $this->bitrate * 1000 / 8);
        } else
        {
            $this->samples = $this->samples_per_frame * $this->frames;
            $s = $this->samples / $this->frequency;

            $this->length = sprintf('%02d:%02d', floor($s / 60), floor($s - (floor($s / 60) * 60)));
            $this->lengthh = sprintf('%02d:%02d:%02d', floor($s / 3600), floor($s / 60), floor($s - (floor($s / 60) * 60)));
            $this->lengths = (int)$s;

            $this->bitrate = (int)(($this->musicsize / $s) * 8 / 1000);
        }

        if ($this->debug)
            print ($this->debugend);
    }

    function getGenre($genreno)
    {
        if ($this->debug)
            print ($this->debugbeg . "getgenre($genreno)<HR>\n");

        $genres = $this->genres();
        if (isset($genres[$genreno]))
        {
            $genre = $genres[$genreno];
            if ($this->debug)
                print ($genre . "\n");
        } else
        {
            $genre = '';
        }

        if ($this->debug)
            print ($this->debugend);
        return $genre;
    }

    function getGenreNo($genre, $default = 0xff)
    {
        if ($this->debug)
            print ($this->debugbeg . "getgenreno('$genre',$default)<HR>\n");

        $genres = $this->genres();
        $genreno = false;
        if ($genre)
        {
            foreach ($genres as $no => $name)
            {
                if (strtolower($genre) == strtolower($name))
                {
                    if ($this->debug)
                        print ("$no:'$name' == '$genre'");
                    $genreno = $no;
                }
            }
        }
        if ($genreno === false)
            $genreno = $default;
        if ($this->debug)
            print ($this->debugend);
        return $genreno;
    }

    function genres()
    {
        return array(0 => 'Blues', 1 => 'Classic Rock', 2 => 'Country', 3 => 'Dance', 4 => 'Disco', 5 => 'Funk', 6 => 'Grunge', 7 => 'Hip-Hop', 8 => 'Jazz', 9 => 'Metal', 10 => 'New Age', 11 => 'Oldies', 12 => 'Other', 13 => 'Pop', 14 => 'R&B', 15 =>
            'Rap', 16 => 'Reggae', 17 => 'Rock', 18 => 'Techno', 19 => 'Industrial', 20 => 'Alternative', 21 => 'Ska', 22 => 'Death Metal', 23 => 'Pranks', 24 => 'Soundtrack', 25 => 'Euro-Techno', 26 => 'Ambient', 27 => 'Trip-Hop', 28 => 'Vocal', 29 =>
            'Jazz+Funk', 30 => 'Fusion', 31 => 'Trance', 32 => 'Classical', 33 => 'Instrumental', 34 => 'Acid', 35 => 'House', 36 => 'Game', 37 => 'Sound Clip', 38 => 'Gospel', 39 => 'Noise', 40 => 'Alternative Rock', 41 => 'Bass', 42 => 'Soul', 43 =>
            'Punk', 44 => 'Space', 45 => 'Meditative', 46 => 'Instrumental Pop', 47 => 'Instrumental Rock', 48 => 'Ethnic', 49 => 'Gothic', 50 => 'Darkwave', 51 => 'Techno-Industrial', 52 => 'Electronic', 53 => 'Pop-Folk', 54 => 'Eurodance', 55 =>
            'Dream', 56 => 'Southern Rock', 57 => 'Comedy', 58 => 'Cult', 59 => 'Gangsta', 60 => 'Top 40', 61 => 'Christian Rap', 62 => 'Pop/Funk', 63 => 'Jungle', 64 => 'Native US', 65 => 'Cabaret', 66 => 'New Wave', 67 => 'Psychadelic', 68 => 'Rave',
            69 => 'Showtunes', 70 => 'Trailer', 71 => 'Lo-Fi', 72 => 'Tribal', 73 => 'Acid Punk', 74 => 'Acid Jazz', 75 => 'Polka', 76 => 'Retro', 77 => 'Musical', 78 => 'Rock & Roll', 79 => 'Hard Rock', 80 => 'Folk', 81 => 'Folk-Rock', 82 =>
            'National Folk', 83 => 'Swing', 84 => 'Fast Fusion', 85 => 'Bebob', 86 => 'Latin', 87 => 'Revival', 88 => 'Celtic', 89 => 'Bluegrass', 90 => 'Avantgarde', 91 => 'Gothic Rock', 92 => 'Progressive Rock', 93 => 'Psychedelic Rock', 94 =>
            'Symphonic Rock', 95 => 'Slow Rock', 96 => 'Big Band', 97 => 'Chorus', 98 => 'Easy Listening', 99 => 'Acoustic', 100 => 'Humour', 101 => 'Speech', 102 => 'Chanson', 103 => 'Opera', 104 => 'Chamber Music', 105 => 'Sonata', 106 => 'Symphony',
            107 => 'Booty Bass', 108 => 'Primus', 109 => 'Porn Groove', 110 => 'Satire', 111 => 'Slow Jam', 112 => 'Club', 113 => 'Tango', 114 => 'Samba', 115 => 'Folklore', 116 => 'Ballad', 117 => 'Power Ballad', 118 => 'Rhytmic Soul', 119 =>
            'Freestyle', 120 => 'Duet', 121 => 'Punk Rock', 122 => 'Drum Solo', 123 => 'Acapella', 124 => 'Euro-House', 125 => 'Dance Hall', 126 => 'Goa', 127 => 'Drum & Bass', 128 => 'Club-House', 129 => 'Hardcore', 130 => 'Terror', 131 => 'Indie',
            132 => 'BritPop', 133 => 'Negerpunk', 134 => 'Polsk Punk', 135 => 'Beat', 136 => 'Christian Gangsta Rap', 137 => 'Heavy Metal', 138 => 'Black Metal', 139 => 'Crossover', 140 => 'Contemporary Christian', 141 => 'Christian Rock', 142 =>
            'Merengue', 143 => 'Salsa', 144 => 'Trash Metal', 145 => 'Anime', 146 => 'Jpop', 147 => 'Synthpop');
    }
}

?>