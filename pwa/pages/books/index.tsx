import { type GetServerSideProps } from "next";

import { List } from "@/components/book/List";
import { type Book } from "@/types/Book";
import { type PagedCollection } from "@/types/collection";
import { type FetchResponse, fetch } from "@/utils/dataAccess";
import { type FiltersProps, buildUriFromFilters } from "@/utils/book";

export const getServerSideProps: GetServerSideProps<{
  data: PagedCollection<Book> | null,
  hubURL: string | null,
  page: number,
  filters: FiltersProps,
}> = async ({ query }) => {
  const page = query.page ? Number(query.page) : 1;

  const filters: FiltersProps = {};
  if (query.author) {
    // @ts-ignore
    filters.author = query.author;
  }
  if (query.title) {
    // @ts-ignore
    filters.title = query.title;
  }
  if (query.condition) {
    // @ts-ignore
    filters.condition = [query.condition];
  } else if (typeof query["condition[]"] === "string") {
    filters.condition = [query["condition[]"]];
  } else if (typeof query["condition[]"] === "object") {
    filters.condition = query["condition[]"];
  }

  try {
    const response: FetchResponse<PagedCollection<Book>> | undefined = await fetch(buildUriFromFilters("/books", filters, page));
    if (!response?.data) {
      throw new Error('Unable to retrieve data from /books.');
    }

    return { props: { data: response.data, hubURL: response.hubURL, filters, page } };
  } catch (error) {
    console.error(error);
  }

  return { props: { data: null, hubURL: null, filters, page } };
};

export default List;
