#!/bin/bash
echo "WPLoyalty - Point sharing"
current_dir="$PWD"
plugin_name="wp-loyalty-point-sharing"
pack_folder=$current_dir"/../compressed_pack"
plugin_compress_folder=$pack_folder"/"$plugin_name
compile_scss() {
  echo "Compiling SCSS..."

  # Path change if needed
  sass "$current_dir/Assets/Admin/Css/wlps-admin.scss" \
       "$current_dir/Assets/Admin/Css/wlps-admin.css" \
       --no-source-map --style=compressed

  sass "$current_dir/Assets/Site/Css/transfer-modal.scss" \
        "$current_dir/Assets/Site/Css/transfer-modal.css" \
        --no-source-map --style=compressed

  echo "SCSS compiled ✔"
}
composer_run() {
  # shellcheck disable=SC2164
  cd "$current_dir"
  composer install --no-dev
  composer update --no-dev
  cd ..
  echo "Compress Done"
  # shellcheck disable=SC2164
  cd "$current_dir"
}
copy_folder() {
  if [ -d "$pack_folder" ]; then
    rm -r "$pack_folder"
  fi
  mkdir "$pack_folder"
  mkdir "$plugin_compress_folder"
  move_dir=("App" "Assets" "templates" "vendor" "i18n" "composer.json" $plugin_name".php")
  # shellcheck disable=SC2068
  for dir in ${move_dir[@]}; do
    cp -r "$current_dir/$dir" "$plugin_compress_folder/$dir"
  done
}
update_ini_file() {
  cd $current_dir
  wp i18n make-pot . "i18n/languages/$plugin_name.pot" --slug="$plugin_name" --domain="$plugin_name" --include=$plugin_name".php"
  cd $current_dir
  echo "Update ini done"
}
zip_folder() {
  cd "$pack_folder"
  rm "$plugin_name".zip
  zip -r "$plugin_name".zip $plugin_name -q
  zip -d "$plugin_name".zip __MACOSX/\*
  zip -d "$plugin_name".zip \*/.DS_Store
}
echo "Scss Compiled:"
compile_scss
echo "Composer Run:"
composer_run
echo "Update ini"
update_ini_file
echo "Copy Folder:"
copy_folder
echo "Zip Folder:"
zip_folder
echo "End"