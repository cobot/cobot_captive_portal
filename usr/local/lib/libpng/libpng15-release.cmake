#----------------------------------------------------------------
# Generated CMake target import file for configuration "Release".
#----------------------------------------------------------------

# Commands may need to know the format version.
set(CMAKE_IMPORT_FILE_VERSION 1)

# Import target "png15" for configuration "Release"
set_property(TARGET png15 APPEND PROPERTY IMPORTED_CONFIGURATIONS RELEASE)
set_target_properties(png15 PROPERTIES
  IMPORTED_LINK_INTERFACE_LIBRARIES_RELEASE "/usr/lib/libz.so;/usr/lib/libm.so"
  IMPORTED_LOCATION_RELEASE "${_IMPORT_PREFIX}/lib/libpng15.so.15.17"
  IMPORTED_SONAME_RELEASE "libpng15.so.15"
  )

list(APPEND _IMPORT_CHECK_TARGETS png15 )
list(APPEND _IMPORT_CHECK_FILES_FOR_png15 "${_IMPORT_PREFIX}/lib/libpng15.so.15.17" )

# Import target "png15_static" for configuration "Release"
set_property(TARGET png15_static APPEND PROPERTY IMPORTED_CONFIGURATIONS RELEASE)
set_target_properties(png15_static PROPERTIES
  IMPORTED_LINK_INTERFACE_LANGUAGES_RELEASE "C"
  IMPORTED_LINK_INTERFACE_LIBRARIES_RELEASE "/usr/lib/libz.so;/usr/lib/libm.so"
  IMPORTED_LOCATION_RELEASE "${_IMPORT_PREFIX}/lib/libpng15.a"
  )

list(APPEND _IMPORT_CHECK_TARGETS png15_static )
list(APPEND _IMPORT_CHECK_FILES_FOR_png15_static "${_IMPORT_PREFIX}/lib/libpng15.a" )

# Commands beyond this point should not need to know the version.
set(CMAKE_IMPORT_FILE_VERSION)
